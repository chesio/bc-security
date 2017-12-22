<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Modules\IpBlacklist;

/**
 * Logs table
 */
class ListTable extends \BlueChip\Security\Core\ListTable
{
    /** @var string Name of blacklist action query argument */
    const ACTION_BLACKLIST = 'blacklist';

    /** @var string Name of view query argument */
    const VIEW_EVENT = 'event';


    /** @var \BlueChip\Security\Modules\Log\Logger */
    private $logger;

    /** @var \BlueChip\Security\Modules\Log\Event */
    private $event = null;


    /**
     * @param string $url
     * @param string $per_page_option_name
     * @param \BlueChip\Security\Modules\Log\Logger $logger
     */
    public function __construct(string $url, string $per_page_option_name, Logger $logger)
    {
        parent::__construct($url, $per_page_option_name);

        $this->logger = $logger;

        // Display only events of particular type?
        $event_id = filter_input(INPUT_GET, self::VIEW_EVENT, FILTER_SANITIZE_URL);
        if ($event_id && in_array($event_id, Event::enlist(), true)) {
            $this->event = Event::create($event_id);
            $this->url = add_query_arg(self::VIEW_EVENT, $event_id, $this->url);
        }
    }


    /**
     * Return content for first column (date and time) including row actions.
     *
     * @param array $item
     * @return string
     */
    public function column_date_and_time(array $item): string // phpcs:ignore
    {
        return $item['date_and_time'] . $this->row_actions($this->getRowActions($item));
    }


    /**
     * Return column contents.
     *
     * @param array $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name) // phpcs:ignore
    {
        if ($this->event && $this->event->hasContext($column_name)) {
            $context = empty($item['context']) ? [] : unserialize($item['context']);
            $value = isset($context[$column_name]) ? $context[$column_name] : '';
            // Value can be an array, in such case output array values separated by ",".
            return is_array($value) ? implode(', ', $value) : $value;
        } else {
            return isset($item[$column_name]) ? $item[$column_name] : '';
        }
    }


    /**
     * Return content for event type column.
     *
     * @param array $item
     * @return string
     */
    public function column_event(array $item): string // phpcs:ignore
    {
        $event = Event::create($item['event']);
        return $event ? $event->getName() : '';
    }


    /**
     * Return content for message column.
     *
     * @param array $item
     * @return string
     */
    public function column_message(array $item): string // phpcs:ignore
    {
        $message = empty($item['message']) ? '' : $item['message'];
        $context = empty($item['context']) ? [] : unserialize($item['context']);
        return self::formatMessage($message, $context);
    }


    /**
     * Define table columns
     * @return array
     */
    public function get_columns() // phpcs:ignore
    {
        $columns = [
            'date_and_time' => __('Date and time', 'bc-security'),
            'ip_address' => __('IP address', 'bc-security'),
        ];

        if ($this->event) {
            foreach ($this->event->getContext() as $id => $name) {
                // Do not override existing columns.
                if (!isset($columns[$id])) {
                    $columns[$id] = $name;
                }
            }
        } else {
            $columns['event'] = __('Event', 'bc-security');
            $columns['message'] = __('Message', 'bc-security');
        }

        return $columns;
    }


    /**
     * Define sortable columns
     * @return array
     */
    public function get_sortable_columns() // phpcs:ignore
    {
        return [
            'date_and_time' => 'date_and_time',
            'ip_address' => 'ip_address',
            'event' => 'event',
        ];
    }


    /**
     * Define available views for this table.
     * @return array
     */
    protected function get_views() // phpcs:ignore
    {
        $event_id = is_null($this->event) ? null : $this->event->getId();

        $views = [
            'all' => sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                remove_query_arg([self::VIEW_EVENT], $this->url),
                is_null($event_id) ? 'current' : '',
                esc_html__('All', 'bc-security'),
                $this->logger->countAll()
            ),
        ];

        foreach (Event::enlist() as $eid) {
            // Get human readable name for event type.
            if (empty($event = Event::create($eid))) {
                // Ignore event IDs unknown to the plugin (there should be none, but better to be safe).
                continue;
            }

            $views[$eid] = sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                add_query_arg([self::VIEW_EVENT => $eid], $this->url),
                $event_id === $eid ? 'current' : '',
                esc_html($event->getName()),
                $this->logger->countAll($eid)
            );
        }

        return $views;
    }


    /**
     * Prepare items for table.
     */
    public function prepare_items() // phpcs:ignore
    {
        $event_id = $this->event ? $this->event->getId() : null;

        $current_page = $this->get_pagenum();
        $per_page = $this->items_per_page;

        $total_items = $this->logger->countAll($event_id);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);

        $this->items = $this->logger->fetch($event_id, ($current_page - 1) * $per_page, $per_page, $this->order_by, $this->order);
    }


    /**
     * @param array $item
     * @return array
     */
    private function getRowActions(array $item): array
    {
        if (empty($scope = $this->getLockScopeFromEvent($item['event']))) {
            // No scope, no action.
            return [];
        }

        return [
            self::ACTION_BLACKLIST => sprintf(
                '<span class="delete"><a href="%s">%s</a></span>',
                add_query_arg(
                    [
                        IpBlacklist\AdminPage::DEFAULT_IP_ADDRESS => $item['ip_address'],
                        IpBlacklist\AdminPage::DEFAULT_SCOPE => $scope,
                    ],
                    IpBlacklist\AdminPage::getPageUrl()
                ),
                esc_html__('Add to blacklist', 'bc-security')
            ),
        ];
    }


    /**
     * Return appropriate lock scope for $event type.
     *
     * @see \BlueChip\Security\Modules\Log\Event
     *
     * @param string $event_id One from event IDs defined in \BlueChip\Security\Modules\Log\Event.
     * @return int|null Return null, if given $event does not warrant blacklisting,
     * otherwise return lock scope code.
     */
    private function getLockScopeFromEvent(string $event_id)
    {
        switch ($event_id) {
            case Event::QUERY_404:
                return IpBlacklist\LockScope::WEBSITE;
            case Event::AUTH_BAD_COOKIE:
            case Event::LOGIN_FAILURE:
            case Event::LOGIN_LOCKOUT:
                return IpBlacklist\LockScope::ADMIN;
            default:
                return null;
        }
    }


    /**
     * Replace placeholders in $message with values from $context.
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    private static function formatMessage(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            // Format array as comma separated list (indicate empty array with "-")
            // Convert all other values to string (and make the value stand out in bold in such case).
            $formatted = is_array($value) ? (empty($value) ? '-' : implode(', ', $value)) : sprintf('<strong>%s</strong>', $value);
            // Inject formatted values into message.
            $message = str_replace("{{$key}}", $formatted, $message);
        }

        return $message;
    }
}
