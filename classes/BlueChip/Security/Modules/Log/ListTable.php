<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Core\ListTable as CoreListTable;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\InternalBlocklist\AdminPage as InternalBlocklistAdminPage;

/**
 * Logs table
 */
class ListTable extends CoreListTable
{
    /**
     * @var string Name of blocklist action query argument
     */
    private const ACTION_BLOCKLIST = 'blocklist';

    /**
     * @var string Name of view query argument
     */
    private const VIEW_EVENT = 'event';


    private Logger $logger;

    private ?Event $event = null;


    public function __construct(string $url, string $per_page_option_name, Logger $logger)
    {
        parent::__construct($url, $per_page_option_name);

        $this->logger = $logger;

        // Display only events of particular type?
        $event_id = \filter_input(INPUT_GET, self::VIEW_EVENT, FILTER_SANITIZE_URL);
        if ($event_id && !empty($event = EventsManager::create($event_id))) {
            $this->event = $event;
            $this->url = add_query_arg(self::VIEW_EVENT, $event_id, $this->url);
        }
    }


    /**
     * @param string $url
     * @param string $event_id
     *
     * @return string URL made from $url with query argument for view with $event_id appended.
     */
    public static function getViewUrl(string $url, string $event_id): string
    {
        return add_query_arg(self::VIEW_EVENT, $event_id, $url);
    }


    /**
     * Return content for first column (date and time) including row actions.
     *
     * @param array<string,string> $item
     *
     * @return string
     */
    public function column_date_and_time(array $item): string // phpcs:ignore
    {
        return $this->formatDateAndTime($item['date_and_time']) . $this->row_actions($this->getRowActions($item));
    }


    /**
     * Return column contents.
     *
     * @param array<string,string> $item
     * @param string $column_name
     *
     * @return string
     */
    public function column_default($item, $column_name) // phpcs:ignore
    {
        if ($this->event && \array_key_exists($column_name, $this->event->getContext())) {
            $context = empty($item['context']) ? [] : \unserialize($item['context']);
            $value = $context[$column_name] ?? '';
            // Value can be an array, in such case output array values separated by ",".
            return \is_array($value) ? \implode(', ', $value) : $value;
        } else {
            return $item[$column_name] ?? '';
        }
    }


    /**
     * Return content for event type column.
     *
     * @param array<string,string> $item
     *
     * @return string
     */
    public function column_event(array $item): string // phpcs:ignore
    {
        $event = EventsManager::create($item['event']);
        // In case of unknown event, just return event ID in italics.
        return $event ? $event->getName() : ('<i>' . $item['event'] . '</i>');
    }


    /**
     * Return content for IP address column.
     *
     * @param array<string,string> $item
     *
     * @return string
     */
    public function column_ip_address(array $item): string // phpcs:ignore
    {
        $value = $item['ip_address'];
        // Display hostname below IP address, but only if it has been successfully resolved.
        if (!empty($item['hostname']) && ($item['hostname'] !== $item['ip_address'])) {
            $value .= '<br><em>' . esc_html($item['hostname']) . '</em>';
        }
        return $value;
    }


    /**
     * Return content for message column.
     *
     * @param array<string,string> $item
     *
     * @return string
     */
    public function column_message(array $item): string // phpcs:ignore
    {
        $message = empty($item['message']) ? '' : $item['message'];
        $context = empty($item['context']) ? [] : \unserialize($item['context']);
        return self::formatMessage($message, $context);
    }


    /**
     * Define table columns
     *
     * @return array<string,string>
     */
    public function get_columns() // phpcs:ignore
    {
        $columns = [
            'date_and_time' => __('Date and time', 'bc-security'),
            'ip_address' => __('IP address', 'bc-security'),
        ];

        if ($this->event) {
            foreach ($this->event->explainContext() as $id => $label) {
                // Do not override existing columns.
                if (!isset($columns[$id])) {
                    $columns[$id] = $label;
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
     *
     * @return array<string,string>
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
     *
     * @return array<string,string>
     */
    protected function get_views() // phpcs:ignore
    {
        $event_id = null === $this->event ? null : $this->event->getId();

        $views = [
            'all' => \sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                remove_query_arg([self::VIEW_EVENT], $this->url),
                null === $event_id ? 'current' : '',
                esc_html__('All', 'bc-security'),
                $this->logger->countAll()
            ),
        ];

        foreach (EventsManager::getInstances() as $eid => $event) {
            // Get human readable name for event type.

            $views[$eid] = \sprintf(
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
     *
     * @return void
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
     * @param array<string,string> $item
     *
     * @return array<string,string>
     */
    private function getRowActions(array $item): array
    {
        if (($access_scope = $this->getLockScopeFromEvent($item['event'])) === Scope::ANY) {
            // No specific scope, no action.
            return [];
        }

        return [
            self::ACTION_BLOCKLIST => \sprintf(
                '<span class="delete"><a href="%s">%s</a></span>',
                add_query_arg(
                    [
                        InternalBlocklistAdminPage::DEFAULT_IP_ADDRESS => $item['ip_address'],
                        InternalBlocklistAdminPage::DEFAULT_SCOPE => $access_scope->value,
                    ],
                    InternalBlocklistAdminPage::getPageUrl()
                ),
                esc_html__('Add to internal blocklist', 'bc-security')
            ),
        ];
    }


    /**
     * Return appropriate access scope for lock based on $event type.
     *
     * @see \BlueChip\Security\Modules\Access\Scope
     *
     * @param string $event_id One from event IDs defined in \BlueChip\Security\Modules\Log\Event.
     *
     * @return Scope Lock access scope - Scope::ANY indicates that given event does not warrant blocklisting.
     */
    private function getLockScopeFromEvent(string $event_id): Scope
    {
        switch ($event_id) {
            case Events\Query404::ID:
                return Scope::WEBSITE;
            case Events\AuthBadCookie::ID:
            case Events\LoginFailure::ID:
            case Events\LoginLockout::ID:
                return Scope::ADMIN;
            default:
                return Scope::ANY;
        }
    }


    /**
     * Replace placeholders in $message with values from $context.
     *
     * @param string $message
     * @param array<string,mixed> $context
     *
     * @return string
     */
    private static function formatMessage(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            // Format array as comma separated list (indicate empty array with "-")
            // Convert all other values to string (and make the value stand out in bold in such case).
            $formatted = \is_array($value) ? (empty($value) ? '-' : \implode(', ', $value)) : \sprintf('<strong>%s</strong>', $value);
            // Inject formatted values into message.
            $message = \str_replace("{{$key}}", $formatted, $message);
        }

        return $message;
    }
}
