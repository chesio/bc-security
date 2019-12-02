<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\IpBlacklist;

/**
 * IP blacklist table
 */
class ListTable extends \BlueChip\Security\Core\ListTable
{
    /**
     * @var string Name of remove action query argument
     */
    const ACTION_REMOVE = 'remove';

    /**
     * @var string Name of unlock action query argument
     */
    const ACTION_UNLOCK = 'unlock';

    /**
     * @var string Name of bulk remove action
     */
    const BULK_ACTION_REMOVE = 'bulk-remove';

    /**
     * @var string Name of bulk unlock action
     */
    const BULK_ACTION_UNLOCK = 'bulk-unlock';

    /**
     * @var string Name of removed notice query argument
     */
    const NOTICE_RECORD_REMOVED = 'removed';

    /**
     * @var string Name of unlocked notice query argument
     */
    const NOTICE_RECORD_UNLOCKED = 'unlocked';

    /**
     * @var string Name of view query argument
     */
    const VIEW_SCOPE = 'scope';


    /**
     * @var \BlueChip\Security\Modules\IpBlacklist\Manager
     */
    private $bl_manager;

    /**
     * @var int
     */
    private $scope;


    /**
     * @param string $url
     * @param string $per_page_option_name
     * @param \BlueChip\Security\Modules\IpBlacklist\Manager $bl_manager
     */
    public function __construct(string $url, string $per_page_option_name, Manager $bl_manager)
    {
        parent::__construct($url, $per_page_option_name);

        $this->bl_manager = $bl_manager;

        $this->scope = \filter_input(INPUT_GET, self::VIEW_SCOPE, FILTER_VALIDATE_INT, ['options' => ['default' => LockScope::ANY]]);
        if ($this->scope !== LockScope::ANY) {
            $this->url = add_query_arg(self::VIEW_SCOPE, $this->scope, $this->url);
        }
    }


    /**
     * Return content for first column (IP address) including row actions.
     *
     * @param array $item
     * @return string
     */
    public function column_ip_address(array $item): string // phpcs:ignore
    {
        return $item['ip_address'] . $this->row_actions($this->getRowActions($item));
    }


    /**
     * Return human readable value for ban reason table column.
     *
     * @param array $item
     * @return string
     */
    public function column_reason(array $item): string // phpcs:ignore
    {
        return $this->explainBanReason($item['reason']);
    }


    /**
     * Display (dismissible) admin notices informing user that an action has been performed successfully.
     */
    public function displayNotices()
    {
        $this->displayNotice(
            self::NOTICE_RECORD_REMOVED,
            'Selected record has been removed.',
            'Selected records have been removed.'
        );

        $this->displayNotice(
            self::NOTICE_RECORD_UNLOCKED,
            'Selected record has been unlocked.',
            'Selected records have been unlocked.'
        );
    }


    /**
     * @return array
     */
    public function get_bulk_actions() // phpcs:ignore
    {
        return [
            self::BULK_ACTION_UNLOCK => __('Unlock', 'bc-security'),
            self::BULK_ACTION_REMOVE => __('Remove', 'bc-security'),
        ];
    }


    /**
     * Define table columns.
     *
     * @return array
     */
    public function get_columns() // phpcs:ignore
    {
        return [
            'cb' => '<input type="checkbox">',
            'ip_address' => __('IP address', 'bc-security'),
            'ban_time' => __('Ban date and time', 'bc-security'),
            'release_time' => __('Release date and time', 'bc-security'),
            'reason' => __('Ban reason', 'bc-security'),
            'comment' => __('Comment', 'bc-security'),
        ];
    }


    /**
     * Define sortable columns.
     *
     * @return array
     */
    public function get_sortable_columns() // phpcs:ignore
    {
        return [
            'ip_address' => 'ip_address',
            'ban_time' => 'ban_time',
            'release_time' => 'release_time',
            'reason' => 'reason',
        ];
    }


    /**
     * Define available views for this table.
     *
     * @todo Better labels for scopes.
     *
     * @return array
     */
    protected function get_views() // phpcs:ignore
    {
        return [
            'any' => \sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                remove_query_arg([self::VIEW_SCOPE], $this->url),
                $this->scope === LockScope::ANY ? 'current' : '',
                esc_html__('Any', 'bc-security'),
                $this->bl_manager->countAll()
            ),
            'admin' => \sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                add_query_arg([self::VIEW_SCOPE => LockScope::ADMIN], $this->url),
                $this->scope === LockScope::ADMIN ? 'current' : '',
                esc_html__('Admin', 'bc-security'),
                $this->bl_manager->countAll(LockScope::ADMIN)
            ),
            'comments' => \sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                add_query_arg([self::VIEW_SCOPE => LockScope::COMMENTS], $this->url),
                $this->scope === LockScope::COMMENTS ? 'current' : '',
                esc_html__('Comments', 'bc-security'),
                $this->bl_manager->countAll(LockScope::COMMENTS)
            ),
            'website' => \sprintf(
                '<a href="%s" class="%s">%s</a> (%d)',
                add_query_arg([self::VIEW_SCOPE => LockScope::WEBSITE], $this->url),
                $this->scope === LockScope::WEBSITE ? 'current' : '',
                esc_html__('Website', 'bc-security'),
                $this->bl_manager->countAll(LockScope::WEBSITE)
            ),
        ];
    }


    /**
     * Prepare items for table.
     */
    public function prepare_items() // phpcs:ignore
    {
        $current_page = $this->get_pagenum();
        $per_page = $this->items_per_page;

        $total_items = $this->bl_manager->countAll($this->scope);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);

        $this->items = $this->bl_manager->fetch($this->scope, ($current_page - 1) * $per_page, $per_page, $this->order_by, $this->order);
    }


    /**
     * Process any actions like unlocking etc.
     *
     * @return void
     */
    public function processActions()
    {
        // Remove or unlock single record?
        if (($action = \filter_input(INPUT_GET, 'action'))) {
            // Get ID of record to act upon.
            $id = \filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!\is_int($id)) {
                return;
            }

            $nonce = \filter_input(INPUT_GET, self::NONCE_NAME);
            if (!wp_verify_nonce($nonce, \sprintf('%s:%s', $action, $id))) {
                // Nonce check failed
                return;
            }

            if (($action === self::ACTION_REMOVE) && $this->bl_manager->remove($id)) {
                // Record removed successfully, redirect to overview (and trigger admin notice)
                wp_redirect(add_query_arg(self::NOTICE_RECORD_REMOVED, 1, $this->url));
            }

            if (($action === self::ACTION_UNLOCK) && $this->bl_manager->unlock($id)) {
                // Record unlocked successfully, redirect to overview (and trigger admin notice)
                wp_redirect(add_query_arg(self::NOTICE_RECORD_UNLOCKED, 1, $this->url));
            }
        }

        // Bulk unlock?
        if (($current_action = $this->current_action()) && isset($_POST['ids']) && \is_array($_POST['ids'])) {
            // Sanitize: convert IDs to unsigned int and remove any zero values.
            $ids = \array_filter(\array_map('absint', $_POST['ids']));

            if ($current_action === self::BULK_ACTION_REMOVE && ($removed = $this->bl_manager->removeMany($ids))) {
                // Records removed successfully, redirect to overview (and trigger admin notice)
                wp_redirect(add_query_arg(self::NOTICE_RECORD_REMOVED, $removed, $this->url));
            }

            if ($current_action === self::BULK_ACTION_UNLOCK && ($unlocked = $this->bl_manager->unlockMany($ids))) {
                // Records unlocked successfully, redirect to overview (and trigger admin notice)
                wp_redirect(add_query_arg(self::NOTICE_RECORD_UNLOCKED, $unlocked, $this->url));
            }
        }
    }


    /**
     * Translate integer code for ban reason into something human can read.
     *
     * @param int $banReason
     * @return string
     */
    private function explainBanReason(int $banReason): string
    {
        switch ($banReason) {
            case BanReason::LOGIN_LOCKOUT_SHORT:
            case BanReason::LOGIN_LOCKOUT_LONG:
                return _x('Too many failed login attempts', 'Ban reason', 'bc-security');
            case BanReason::USERNAME_BLACKLIST:
                return _x('Login attempt using blacklisted username', 'Ban reason', 'bc-security');
            case BanReason::MANUALLY_BLACKLISTED:
                return _x('Manually blacklisted', 'Ban reason', 'bc-security');
            default:
                return _x('Unknown', 'Ban reason', 'bc-security');
        }
    }


    /**
     * @param array $item
     * @return array
     */
    private function getRowActions(array $item): array
    {
        $actions = [
            // Any item can be removed
            self::ACTION_REMOVE => $this->renderRowAction(
                self::ACTION_REMOVE,
                $item['id'],
                'delete',
                __('Remove', 'bc-security')
            ),
        ];

        if (\strtotime($item['release_time']) > current_time('timestamp')) {
            // Only active locks can be unlocked
            $actions[self::ACTION_UNLOCK] = $this->renderRowAction(
                self::ACTION_UNLOCK,
                $item['id'],
                'unlock',
                __('Unlock', 'bc-security')
            );
        }

        return $actions;
    }
}
