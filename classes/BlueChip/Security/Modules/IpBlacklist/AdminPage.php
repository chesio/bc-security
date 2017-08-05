<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\IpBlacklist;

use BlueChip\Security\Helpers\AdminNotices;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-ip-blacklist';

    /**
     * @var string Name of nonce used for any action on this page
     */
    const NONCE_NAME = '_wpnonce';

    /**
     * @var Name for blacklist action (used for both nonce action and submit name)
     */
    const BLACKLIST_ACTION = 'add-to-ip-blacklist';

    /**
     * @var Name for prune action (used for both nonce action and submit name)
     */
    const PRUNE_ACTION = 'prune-ip-blacklist';

    /**
     * @var Name for cron activation action (used for both nonce action and submit name)
     */
    const CRON_ACTION_ON = 'auto-ip-blacklist-pruning-on';

    /**
     * @var Name for cron deactivation action (used for both nonce action and submit name)
     */
    const CRON_ACTION_OFF = 'auto-ip-blacklist-pruning-off';

    /**
     * @var Name for query argument that prefills IP address in the form
     */
    const DEFAULT_IP_ADDRESS = 'default-ip-address';

    /**
     * @var Name for query argument that prefills lock scope in the form
     */
    const DEFAULT_SCOPE = 'default-scope';


    /**
     * @var \BlueChip\Security\Modules\IpBlacklist\Manager
     */
    private $bl_manager;

    /**
     * @var \BlueChip\Security\Core\CronJob
     */
    private $bl_cleaner;

    /**
     * @var \BlueChip\Security\Modules\IpBlacklist\ListTable
     */
    private $list_table;


    /**
     * @param \BlueChip\Security\Modules\IpBlacklist\Manager $bl_manager
     * @param \BlueChip\Security\Core\CronJob $bl_cleaner
     */
    public function __construct(Manager $bl_manager, \BlueChip\Security\Core\CronJob $bl_cleaner)
    {
        $this->page_title = _x('IP Blacklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('IP Blacklist', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;

        $this->bl_manager = $bl_manager;
        $this->bl_cleaner = $bl_cleaner;

        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }


    public function loadPage()
    {
        $this->processActions();
        $this->addScreenOptions();
        $this->initListTable();
    }


    /**
     * Render admin page.
     */
    public function render()
    {
        echo '<div class="wrap">';
        // Page heading
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        // Manual blacklist form
        $this->renderBlacklistingForm();
        // Table
        echo '<h2>' . esc_html__('Blacklisted IP addresses', 'bc-security') . '</h2>';
        $this->list_table->views();
        echo '<form method="post">';
        $this->list_table->display();
        echo '</form>';
        // Table pruning actions
        $this->renderPruningActions();
        echo '</div>';
    }


    /**
     * Render form for manual addition of IP addresses to blacklist.
     *
     * @hook \BlueChip\Security\Modules\IpBlacklist\Hooks::DEFAULT_MANUAL_LOCK_DURATION
     */
    private function renderBlacklistingForm()
    {
        // Accept the following values as "pre-fill"
        // Note: the "add-" prefix is especially important for scope, because
        // there is "scope" GET argument used for list table views already.
        $ip_address = filter_input(INPUT_GET, self::DEFAULT_IP_ADDRESS, FILTER_VALIDATE_IP);
        $scope = filter_input(INPUT_GET, self::DEFAULT_SCOPE, FILTER_VALIDATE_INT);

        // Default lock duration is 1 month, unless different value is provided by filter.
        $duration = apply_filters(Hooks::DEFAULT_MANUAL_LOCK_DURATION, 1 * MONTH_IN_SECONDS);

        // Offer the following time constants in <select> box:
        $units_in_seconds = [
            YEAR_IN_SECONDS => __('years', 'bc-security'),
            MONTH_IN_SECONDS => __('months', 'bc-security'),
            WEEK_IN_SECONDS => __('weeks', 'bc-security'),
            DAY_IN_SECONDS => __('days', 'bc-security'),
            HOUR_IN_SECONDS => __('hours', 'bc-security'),
            MINUTE_IN_SECONDS => __('minutes', 'bc-security'),
            1 => __('seconds', 'bc-security'), // SECOND_IN_SECONDS :)
        ];

        // Transform number of seconds into the biggest fitting time unit.
        // For example 172800 seconds are 2 days: $duration = 172800; => $duration_units = 2; $duration_unit_in_seconds => 86400;
        list($duration_units, $duration_unit_in_seconds) = $this->transformSecondsIntoFittingUnit($duration, array_keys($units_in_seconds));

        // Simple styling
        echo '<style>form.bc-security { overflow: hidden; } span.bc-security { float: left; margin-right: 1.5em; margin-bottom: 0.5em; } span.bc-security label { display: block; margin-left: 0.25em; margin-bottom: 0.25em; } span.bc-security input, span.bc-security select { vertical-align: middle; } </style>';

        echo '<h2>' . esc_html__('Manually blacklist IP address', 'bc-security') . '</h2>';
        echo '<form method="post" class="bc-security">';

        // Form nonce
        wp_nonce_field(self::BLACKLIST_ACTION, self::NONCE_NAME);

        // IP address
        echo '<span class="bc-security">';
        echo '<label for="ip-blacklist-ip-address">' . esc_html__('IP Address', 'bc-security') . '</label>';
        echo '<input type="text" id="ip-blacklist-ip-address" name="ip-address" value="' . esc_attr($ip_address) . '" placeholder="' . esc_attr__('AAA.BBB.CCC.DDD', 'bc-security') . '">';
        echo '</span>';

        // Duration
        echo '<span class="bc-security">';
        echo '<label for="ip-blacklist-duration">' . esc_html__('Lock duration', 'bc-security') . '</label>';
        echo '<input type="number" id="ip-blacklist-duration" name="duration-length" class="small-text" value="' . esc_attr($duration_units) . '">';
        echo '<select name="duration-unit">';
        foreach ($units_in_seconds as $unit_in_seconds => $unit_name) {
            echo '<option value="' . $unit_in_seconds . '"' . selected($unit_in_seconds, $duration_unit_in_seconds, false) . '>' . esc_html($unit_name) . '</option>';
        }
        echo '</select>';
        echo '</span>';

        // Lock scope
        echo '<span class="bc-security">';
        echo '<label for="ip-blacklist-scope">' . esc_html('Lock scope', 'bc-security') . '</label>';
        echo '<select id="ip-blacklist-scope" name="scope">';
        echo '<option value="' . LockScope::ADMIN . '"' . selected(LockScope::ADMIN, $scope, false) . '>' . esc_html__('Admin', 'bc-security') . '</option>';
        echo '<option value="' . LockScope::COMMENTS . '"' . selected(LockScope::COMMENTS, $scope, false) . '>' . esc_html__('Comments', 'bc-security') . '</option>';
        echo '<option value="' . LockScope::WEBSITE . '"' . selected(LockScope::WEBSITE, $scope, false) . '>' . esc_html__('Website', 'bc-security') . '</option>';
        echo '</select>';
        echo '</span>';

        // Optional comment
        echo '<span class="bc-security">';
        echo '<label for="ip-blacklist-comment">' . esc_html('Comment', 'bc-security') . '</label>';
        echo '<input type="text" id="ip-blacklist-comment" name="comment" size="30" placeholder="' . esc_attr__('Comment is optional...', 'bc-security') . '" maxlength="255">';
        echo '</span>';

        // Submit button
        echo '<span class="bc-security">';
        echo '<label>&nbsp;</label>'; // Dummy label
        submit_button(__('Add to blacklist', 'bc-security'), 'primary', self::BLACKLIST_ACTION, false);
        echo '</span>';

        echo '</form>';
    }


    /**
     * Render forms for IP blacklist pruning (including cron job activation and deactivation).
     */
    private function renderPruningActions()
    {
        echo '<h2>' . esc_html__('Blacklist pruning', 'bc-security') . '</h2>';

        echo '<form method="post">';
        wp_nonce_field(self::PRUNE_ACTION, self::NONCE_NAME);
        echo '<p>' . esc_html__('You can clean up all out-dated records from the IP blacklist manually:', 'bc-security') . '</p>';
        submit_button(__('Prune IP blacklist', 'bc-security'), 'delete', self::PRUNE_ACTION, false);
        echo '</form>';

        echo '<form method="post">';
        if ($this->bl_cleaner->isScheduled()) {
            wp_nonce_field(self::CRON_ACTION_OFF, self::NONCE_NAME);
            echo '<p>' . esc_html__('Automatic clean up of out-dated records is active.', 'bc-security') . '</p>';
            submit_button(__('Deactivate auto-pruning', 'bc-security'), 'delete', self::CRON_ACTION_OFF, false);
        } else {
            wp_nonce_field(self::CRON_ACTION_ON, self::NONCE_NAME);
            echo '<p>' . esc_html__('You can activate automatic clean up of out-dated records via WP-Cron:', 'bc-security') . '</p>';
            submit_button(__('Activate auto-pruning', 'bc-security'), 'primary', self::CRON_ACTION_ON, false);
        }
        echo '</form>';
    }


    /**
     * @param bool $status
     * @param string $option
     * @param string $value
     * @return mixed
     */
    public function setScreenOption($status, $option, $value)
    {
        return ($option === ListTable::RECORDS_PER_PAGE) ? intval($value) : $status;
    }


    private function addScreenOptions()
    {
        add_screen_option('per_page', [
            'label' => __('Records', 'bc-security'),
            'default' => 20,
            'option' => ListTable::RECORDS_PER_PAGE,
        ]);
    }


    /**
     * Initialize list table instance.
     */
    private function initListTable()
    {
        $this->list_table = new ListTable($this->getUrl(), $this->bl_manager);
        $this->list_table->processActions(); // may trigger wp_redirect()
        $this->list_table->displayNotices();
        $this->list_table->prepare_items();
    }


    /**
     * Dispatch any action that is indicated by POST data (form submission).
     */
    private function processActions()
    {
        $nonce = filter_input(INPUT_POST, self::NONCE_NAME, FILTER_SANITIZE_STRING);
        if (empty($nonce)) {
            // No nonce, no action.
            return;
        }

        if (isset($_POST[self::BLACKLIST_ACTION]) && wp_verify_nonce($nonce, self::BLACKLIST_ACTION)) {
            // Manually add an IP address to blacklist.
            $this->processBlacklistAction();
        }

        if (isset($_POST[self::PRUNE_ACTION]) && wp_verify_nonce($nonce, self::PRUNE_ACTION)) {
            // Prune blacklist.
            $this->processPruneAction();
        }

        if (isset($_POST[self::CRON_ACTION_OFF]) && wp_verify_nonce($nonce, self::CRON_ACTION_OFF)) {
            // Deactivate automatic pruning.
            $this->processCronOffAction();
        }

        if (isset($_POST[self::CRON_ACTION_ON]) && wp_verify_nonce($nonce, self::CRON_ACTION_ON)) {
            // Activate automatic pruning.
            $this->processCronOnAction();
        }
    }


    /**
     * Read POST data and attempt to add IP address to blacklist. Display notice about action outcome.
     */
    private function processBlacklistAction()
    {
        $ip_address = filter_input(INPUT_POST, 'ip-address', FILTER_VALIDATE_IP);
        $duration_length = filter_input(INPUT_POST, 'duration-length', FILTER_VALIDATE_INT);
        $duration_unit = filter_input(INPUT_POST, 'duration-unit', FILTER_VALIDATE_INT);
        $scope = filter_input(INPUT_POST, 'scope', FILTER_VALIDATE_INT);
        $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);

        // Check, if input is formally valid.
        if (empty($ip_address) || empty($duration_length) || empty($duration_unit) || empty($scope)) {
            return;
        }

        $duration = $duration_length * $duration_unit;

        // Check, if input is semantically valid.
        if (($duration <= 0) || !in_array($scope, [LockScope::ADMIN, LockScope::COMMENTS, LockScope::WEBSITE], true)) {
            return;
        }

        if ($this->bl_manager->lock($ip_address, $duration, $scope, BanReason::MANUALLY_BLACKLISTED, $comment)) {
            AdminNotices::add(
                sprintf(__('IP address %s has been added to blacklist.', 'bc-security'), $ip_address),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to add IP address to blacklist.', 'bc-security'),
                AdminNotices::ERROR
            );
        }
    }

    /**
     * Attempt to prune blacklist table. Display notice about action outcome.
     */
    private function processPruneAction()
    {
        if ($this->bl_manager->prune()) {
            AdminNotices::add(
                __('Expired entries have been removed from IP blacklist.', 'bc-security'),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to remove expired entries from IP blacklist.', 'bc-security'),
                AdminNotices::ERROR
            );
        }
    }


    /**
     * Deactivate cron job for blacklist table pruning. Display notice about action outcome.
     */
    private function processCronOffAction()
    {
        $this->bl_cleaner->deactivate();
        AdminNotices::add(
            __('Automatic pruning of IP blacklist table has been deactivated.', 'bc-security'),
            AdminNotices::SUCCESS
        );
    }


    /**
     * Activate cron job for blacklist table pruning. Display notice about action outcome.
     */
    private function processCronOnAction()
    {
        if ($this->bl_cleaner->activate()) {
            AdminNotices::add(
                __('Automatic pruning of IP blacklist table has been activated.', 'bc-security'),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to activate automatic pruning of IP blacklist table.', 'bc-security'),
                AdminNotices::ERROR
            );
        }
    }


    /**
     * Return the first value from $units_in_seconds that produces an integer quotient when used as divisor with $seconds being the dividend.
     *
     * In layman terms: for given number of seconds find the biggest time unit (year, month, week, day, hour etc.) that
     * can be used to represent the number of seconds without fractional component.
     *
     * @param int $seconds
     * @param array $units_in_seconds
     * @return array
     */
    private function transformSecondsIntoFittingUnit($seconds, array $units_in_seconds)
    {
        foreach ($units_in_seconds as $unit_in_seconds) {
            $units = $seconds / $unit_in_seconds;
            if (is_int($units)) {
                return [$units, $unit_in_seconds];
            }
        }

        // Return fallback result:
        return [$seconds, 1];
    }
}
