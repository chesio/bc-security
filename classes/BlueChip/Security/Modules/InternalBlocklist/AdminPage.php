<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\InternalBlocklist;

use BlueChip\Security\Core\Admin\AbstractPage;
use BlueChip\Security\Core\Admin\CountablePage;
use BlueChip\Security\Core\Admin\ListingPage;
use BlueChip\Security\Helpers\AdminNotices;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\Cron;
use BlueChip\Security\Modules\Cron\Manager as CronManager;

class AdminPage extends AbstractPage
{
    /** Page has counter indicator */
    use CountablePage;

    /** Page has list table */
    use ListingPage;


    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-internal-blocklist';

    /**
     * @var string Name for blocklist action (used for both nonce action and submit name)
     */
    private const ADD_TO_BLOCKLIST_ACTION = 'add-to-internal-blocklist';

    /**
     * @var string Name for synchronize .htaccess file action (used for both nonce action and submit name)
     */
    private const HTACCESS_SYNC_ACTION = 'synchronize-htaccess-file';

    /**
     * @var string Name for prune action (used for both nonce action and submit name)
     */
    private const PRUNE_ACTION = 'prune-internal-blocklist';

    /**
     * @var string Name for action to activate cron job for automatic pruning (used for both nonce action and submit name)
     */
    private const CRON_PRUNE_ACTION_ON = 'auto-internal-blocklist-pruning-on';

    /**
     * @var string Name for action to deactivate cron job for automatic pruning (used for both nonce action and submit name)
     */
    private const CRON_PRUNE_ACTION_OFF = 'auto-internal-blocklist-pruning-off';

    /**
     * @var string Name for query argument that prefills IP address in the form
     */
    public const DEFAULT_IP_ADDRESS = 'default-ip-address';

    /**
     * @var string Name for query argument that prefills lock scope in the form
     */
    public const DEFAULT_SCOPE = 'default-scope';


    public function __construct(
        private Manager $ib_manager,
        private HtaccessSynchronizer $htaccess_synchronizer,
        private CronManager $cron_manager
    ) {
        $this->page_title = _x('Internal Blocklist', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Internal Blocklist', 'Dashboard menu item name', 'bc-security');

        $this->setCounter($ib_manager);
        $this->setPerPageOption('bc_security_internal_blocklist_records_per_page');
    }


    public function loadPage(): void
    {
        $this->resetCount();
        $this->processActions();
        $this->addPerPageOption();
        $this->initListTable();
    }


    /**
     * Output page contents.
     */
    public function printContents(): void
    {
        echo '<div class="wrap">';
        // Page heading
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        // Internal blocklist form
        $this->printBlocklistForm();
        // Table
        echo '<h2>' . esc_html__('Blocked IP addresses', 'bc-security') . '</h2>';
        $this->list_table->views();
        echo '<form method="post">';
        $this->list_table->display();
        echo '</form>';
        // Table pruning actions
        $this->printPruningActions();
        // Table syncing actions
        $this->printSyncingActions();
        echo '</div>';
    }


    /**
     * Output form for manual addition of IP addresses to internal blocklist.
     *
     * @hook \BlueChip\Security\Modules\InternalBlocklist\Hooks::DEFAULT_MANUAL_LOCK_DURATION
     */
    private function printBlocklistForm(): void
    {
        // IP address and lock scope can be "pre-filled".
        $ip_address = \filter_input(INPUT_GET, self::DEFAULT_IP_ADDRESS, FILTER_VALIDATE_IP);
        $scope_value = \filter_input(INPUT_GET, self::DEFAULT_SCOPE, FILTER_VALIDATE_INT);

        // Default lock duration is 1 month, unless different value is provided by filter.
        $duration = apply_filters(Hooks::DEFAULT_MANUAL_LOCK_DURATION, MONTH_IN_SECONDS);

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
        [$duration_units, $duration_unit_in_seconds] = $this->transformSecondsIntoFittingUnit($duration, \array_keys($units_in_seconds));

        // Simple styling
        echo '<style>form.bc-security { overflow: hidden; } span.bc-security { float: left; margin-right: 1.5em; margin-bottom: 0.5em; } span.bc-security label { display: block; margin-left: 0.25em; margin-bottom: 0.25em; } span.bc-security input, span.bc-security select { vertical-align: middle; } </style>';

        echo '<h2>' . esc_html__('Manually block IP address', 'bc-security') . '</h2>';
        echo '<form method="post" class="bc-security">';

        // Form nonce
        wp_nonce_field(self::ADD_TO_BLOCKLIST_ACTION, self::NONCE_NAME);

        // IP address
        echo '<span class="bc-security">';
        echo '<label for="internal-blocklist-ip-address">' . esc_html__('IP Address', 'bc-security') . '</label>';
        echo '<input type="text" id="internal-blocklist-ip-address" name="ip-address" value="' . esc_attr($ip_address) . '" placeholder="' . esc_attr__('AAA.BBB.CCC.DDD', 'bc-security') . '">';
        echo '</span>';

        // Duration
        echo '<span class="bc-security">';
        echo '<label for="internal-blocklist-duration">' . esc_html__('Lock duration', 'bc-security') . '</label>';
        echo '<input type="number" id="internal-blocklist-duration" name="duration-length" class="small-text" value="' . (string)$duration_units . '">';
        echo '<select name="duration-unit">';
        foreach ($units_in_seconds as $unit_in_seconds => $unit_name) {
            echo '<option value="' . $unit_in_seconds . '"' . selected($unit_in_seconds, $duration_unit_in_seconds, false) . '>' . esc_html($unit_name) . '</option>';
        }
        echo '</select>';
        echo '</span>';

        // Lock scope
        echo '<span class="bc-security">';
        echo '<label for="internal-blocklist-scope">' . esc_html__('Lock scope', 'bc-security') . '</label>';
        echo '<select id="internal-blocklist-scope" name="scope">';
        echo '<option value="' . Scope::ADMIN->value . '"' . selected(Scope::ADMIN->value, $scope_value, false) . '>' . esc_html__('Admin', 'bc-security') . '</option>';
        echo '<option value="' . Scope::COMMENTS->value . '"' . selected(Scope::COMMENTS->value, $scope_value, false) . '>' . esc_html__('Comments', 'bc-security') . '</option>';
        echo '<option value="' . Scope::WEBSITE->value . '"' . selected(Scope::WEBSITE->value, $scope_value, false) . '>' . esc_html__('Website', 'bc-security') . '</option>';
        echo '</select>';
        echo '</span>';

        // Optional comment
        echo '<span class="bc-security">';
        echo '<label for="internal-blocklist-comment">' . esc_html__('Comment', 'bc-security') . '</label>';
        echo '<input type="text" id="internal-blocklist-comment" name="comment" size="30" placeholder="' . esc_attr__('Comment is optional...', 'bc-security') . '" maxlength="255">';
        echo '</span>';

        // Submit button
        echo '<span class="bc-security">';
        echo '<label>&nbsp;</label>'; // Dummy label
        submit_button(__('Add to blocklist', 'bc-security'), 'primary', self::ADD_TO_BLOCKLIST_ACTION, false);
        echo '</span>';

        echo '</form>';
    }


    /**
     * Output forms for internal blocklist pruning (including cron job activation and deactivation).
     */
    private function printPruningActions(): void
    {
        echo '<h2>' . esc_html__('Blocklist pruning', 'bc-security') . '</h2>';

        echo '<form method="post">';
        wp_nonce_field(self::PRUNE_ACTION, self::NONCE_NAME);
        echo '<p>' . esc_html__('You can clean up all out-dated records from the internal blocklist manually:', 'bc-security') . '</p>';
        submit_button(__('Prune internal blocklist', 'bc-security'), 'delete', self::PRUNE_ACTION, false);
        echo '</form>';

        echo '<form method="post">';
        if ($this->cron_manager->getJob(Cron\Jobs::INTERNAL_BLOCKLIST_CLEAN_UP)->isScheduled()) {
            wp_nonce_field(self::CRON_PRUNE_ACTION_OFF, self::NONCE_NAME);
            echo '<p>' . esc_html__('Automatic clean up of out-dated records is active.', 'bc-security') . '</p>';
            submit_button(__('Deactivate auto-pruning', 'bc-security'), 'delete', self::CRON_PRUNE_ACTION_OFF, false);
        } else {
            wp_nonce_field(self::CRON_PRUNE_ACTION_ON, self::NONCE_NAME);
            echo '<p>' . esc_html__('You can activate automatic clean up of out-dated records via WP-Cron:', 'bc-security') . '</p>';
            submit_button(__('Activate auto-pruning', 'bc-security'), 'primary', self::CRON_PRUNE_ACTION_ON, false);
        }
        echo '</form>';
    }


    private function printSyncingActions(): void
    {
        echo '<h2 id="blocklist-synchronization">' . esc_html__('Blocklist synchronization', 'bc-security') . '</h2>';
        if ($this->htaccess_synchronizer->isAvailable()) {
            if ($this->ib_manager->isHtaccessFileInSync()) {
                echo '<p>' . esc_html__('Everything works fine, the rules in .htaccess file are synchronized automatically.', 'bc-security') . '</p>';
            } else {
                echo '<form method="post">';
                wp_nonce_field(self::HTACCESS_SYNC_ACTION, self::NONCE_NAME);
                echo '<p>' . esc_html__('The rules in .htaccess file are out of sync - you should synchronize the internal blocklist with .htaccess file manually:', 'bc-security') . '</p>';
                submit_button(__('Synchronize blocklist with .htaccess file', 'bc-security'), 'primary', self::HTACCESS_SYNC_ACTION, false);
                echo '</form>';
            }
        } else {
            echo '<p>' . sprintf(
                esc_html__('To use this feature, you have to first put following two lines at the top of root %1$s file:', 'bc-security'),
                '<em>.htaccess</em>'
            ) . '</p>';
            echo '<pre>';
            echo $this->htaccess_synchronizer::HEADER_LINE . PHP_EOL;
            echo $this->htaccess_synchronizer::FOOTER_LINE . PHP_EOL;
            echo '</pre>';
        }
    }


    /**
     * Initialize list table instance.
     */
    private function initListTable(): void
    {
        $this->list_table = new ListTable($this->getUrl(), $this->per_page_option_name, $this->ib_manager);
        $this->list_table->processActions(); // may trigger wp_redirect()
        $this->list_table->displayNotices();
        $this->list_table->prepare_items();
    }


    /**
     * Dispatch any action that is indicated by POST data (form submission).
     */
    private function processActions(): void
    {
        $nonce = \filter_input(INPUT_POST, self::NONCE_NAME);
        if (empty($nonce)) {
            // No nonce, no action.
            return;
        }

        if (isset($_POST[self::ADD_TO_BLOCKLIST_ACTION]) && wp_verify_nonce($nonce, self::ADD_TO_BLOCKLIST_ACTION)) {
            // Manually add an IP address to internal blocklist.
            $this->processBlocklistAction();
        }

        if (isset($_POST[self::HTACCESS_SYNC_ACTION]) && wp_verify_nonce($nonce, self::HTACCESS_SYNC_ACTION)) {
            // Synchronize .htaccess file.
            $this->processHtaccessSyncAction();
        }

        if (isset($_POST[self::PRUNE_ACTION]) && wp_verify_nonce($nonce, self::PRUNE_ACTION)) {
            // Prune internal blocklist.
            $this->processPruneAction();
        }

        if (isset($_POST[self::CRON_PRUNE_ACTION_OFF]) && wp_verify_nonce($nonce, self::CRON_PRUNE_ACTION_OFF)) {
            // Deactivate automatic pruning.
            $this->processCronPruneActionDeactivation();
        }

        if (isset($_POST[self::CRON_PRUNE_ACTION_ON]) && wp_verify_nonce($nonce, self::CRON_PRUNE_ACTION_ON)) {
            // Activate automatic pruning.
            $this->processCronPruneActionActivation();
        }
    }


    /**
     * Read POST data and attempt to add IP address to internal blocklist. Display notice about action outcome.
     */
    private function processBlocklistAction(): void
    {
        $ip_address = \filter_input(INPUT_POST, 'ip-address', FILTER_VALIDATE_IP);
        $duration_length = \filter_input(INPUT_POST, 'duration-length', FILTER_VALIDATE_INT);
        $duration_unit = \filter_input(INPUT_POST, 'duration-unit', FILTER_VALIDATE_INT);
        $scope_value = \filter_input(INPUT_POST, 'scope', FILTER_VALIDATE_INT);
        $comment = \strip_tags(\filter_input(INPUT_POST, 'comment'));

        // Check whether input is formally valid.
        if (empty($ip_address) || empty($duration_length) || empty($duration_unit) || empty($scope_value)) {
            return;
        }

        $duration = $duration_length * $duration_unit;

        // Check whether input is semantically valid.
        if (($duration <= 0) || (($access_scope = Scope::tryFrom($scope_value)) === null)) {
            return;
        }

        if ($this->ib_manager->lock($ip_address, $duration, $access_scope, BanReason::MANUALLY_BLOCKED, $comment)) {
            AdminNotices::add(
                \sprintf(__('IP address %s has been added to internal blocklist.', 'bc-security'), $ip_address),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to add IP address to internal blocklist.', 'bc-security'),
                AdminNotices::ERROR
            );
        }
    }

    private function processHtaccessSyncAction(): void
    {
        if ($this->ib_manager->synchronizeWithHtaccessFile()) {
            AdminNotices::add(
                __('Rules in .htaccess file has been synchronized with the internal blocklist.', 'bc-security'),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to synchronize rules in .htaccess file with the internal blocklist.', 'bc-security'),
                AdminNotices::ERROR
            );
        }
    }

    /**
     * Attempt to prune internal blocklist table. Display notice about action outcome.
     */
    private function processPruneAction(): void
    {
        if ($this->ib_manager->prune()) {
            AdminNotices::add(
                __('Expired entries have been removed from internal blocklist.', 'bc-security'),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to remove expired entries from internal blocklist.', 'bc-security'),
                AdminNotices::ERROR
            );
        }
    }


    /**
     * Deactivate cron job for blocklist table pruning. Display notice about action outcome.
     */
    private function processCronPruneActionDeactivation(): void
    {
        $this->cron_manager->deactivateJob(Cron\Jobs::INTERNAL_BLOCKLIST_CLEAN_UP);
        AdminNotices::add(
            __('Automatic pruning of internal blocklist table has been deactivated.', 'bc-security'),
            AdminNotices::SUCCESS
        );
    }


    /**
     * Activate cron job for blocklist table pruning. Display notice about action outcome.
     */
    private function processCronPruneActionActivation(): void
    {
        if ($this->cron_manager->activateJob(Cron\Jobs::INTERNAL_BLOCKLIST_CLEAN_UP)) {
            AdminNotices::add(
                __('Automatic pruning of internal blocklist table has been activated.', 'bc-security'),
                AdminNotices::SUCCESS
            );
        } else {
            AdminNotices::add(
                __('Failed to activate automatic pruning of internal blocklist table.', 'bc-security'),
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
     * @param int[] $units_in_seconds
     *
     * @return array{int,int}
     */
    private function transformSecondsIntoFittingUnit(int $seconds, array $units_in_seconds): array
    {
        foreach ($units_in_seconds as $unit_in_seconds) {
            $units = $seconds / $unit_in_seconds;
            if (\is_int($units)) {
                return [$units, $unit_in_seconds];
            }
        }

        // Return fallback result:
        return [$seconds, 1];
    }
}
