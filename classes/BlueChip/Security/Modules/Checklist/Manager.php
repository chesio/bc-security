<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Helpers\AjaxHelper;
use BlueChip\Security\Modules;
use BlueChip\Security\Modules\Cron\Jobs as CronJobs;
use BlueChip\Security\Modules\Cron\Manager as CronManager;
use wpdb;

class Manager implements Modules\Initializable
{
    /**
     * @var string
     */
    public const ASYNC_CHECK_ACTION = 'bc_security_run_check';

    /**
     * @var Check[]
     */
    private array $checks;

    private AutorunSettings $settings;

    private CronManager $cron_manager;


    /**
     * @param AutorunSettings $settings
     * @param CronManager $cron_manager
     * @param wpdb $wpdb WordPress database access abstraction object
     * @param string $google_api_key
     */
    public function __construct(AutorunSettings $settings, CronManager $cron_manager, wpdb $wpdb, string $google_api_key)
    {
        $this->checks = $this->constructChecks($wpdb, $google_api_key);
        $this->settings = $settings;
        $this->cron_manager = $cron_manager;
    }


    public function init(): void
    {
        // When settings are updated, ensure that cron jobs for advanced checks are properly (de)activated.
        $this->settings->addUpdateHook($this->updateCronJobs(...));
        // Hook into cron job execution.
        add_action(CronJobs::CHECKLIST_CHECK, $this->runBasicChecks(...), 10, 0);
        foreach ($this->getAdvancedChecks() as $advanced_check) {
            add_action($advanced_check->getCronJobHook(), $advanced_check->runInCron(...), 10, 0);
        }
        // Register AJAX handler.
        AjaxHelper::addHandler(self::ASYNC_CHECK_ACTION, $this->runCheck(...));
    }


    /**
     * Construct all checks.
     *
     * @param wpdb $wpdb WordPress database access abstraction object
     * @param string $google_api_key Google API key for project with Safe Browsing API enabled.
     *
     * @return array<string,Check>
     */
    public function constructChecks(wpdb $wpdb, string $google_api_key): array
    {
        return [
            // PHP files editation should be off.
            Checks\PhpFilesEditationDisabled::getId() => new Checks\PhpFilesEditationDisabled(),

            // Directory listings should be disabled.
            Checks\DirectoryListingDisabled::getId() => new Checks\DirectoryListingDisabled(),

            // PHP files in uploads directory should not be accessible on frontend.
            Checks\NoAccessToPhpFilesInUploadsDirectory::getId() => new Checks\NoAccessToPhpFilesInUploadsDirectory(),

            // Display of errors should be off in live environment.
            Checks\DisplayOfPhpErrorsIsOff::getId() => new Checks\DisplayOfPhpErrorsIsOff(),

            // Error log should not be publicly visible if debugging is on.
            Checks\ErrorLogNotPubliclyAccessible::getId() => new Checks\ErrorLogNotPubliclyAccessible(),

            // There should be no obvious usernames.
            Checks\NoObviousUsernamesCheck::getId() => new Checks\NoObviousUsernamesCheck(),

            // No passwords should be hashed with (default) MD5 hash.
            Checks\NoMd5HashedPasswords::getId() => new Checks\NoMd5HashedPasswords($wpdb),

            // PHP version should be supported.
            Checks\PhpVersionSupported::getId() => new Checks\PhpVersionSupported(),

            // There are no modified or unknown WordPress core files.
            Checks\CoreIntegrity::getId() => new Checks\CoreIntegrity(),

            // There are no plugins installed (from WordPress.org) with modified or unknown files.
            Checks\PluginsIntegrity::getId() => new Checks\PluginsIntegrity(),

            // There are no plugins installed that have been removed from plugins directory.
            Checks\NoPluginsRemovedFromDirectory::getId() => new Checks\NoPluginsRemovedFromDirectory(),

            // Site is not blacklisted by Google.
            Checks\SafeBrowsing::getId() => new Checks\SafeBrowsing($google_api_key),
        ];
    }


    public function getCheck(string $id): ?Check
    {
        return $this->checks[$id] ?? null;
    }


    /**
     * Return list of all implemented checks, optionally filtered.
     *
     * @param array{meaningful?:bool,monitored?:bool,status?:?bool} $filters [optional] Extra conditions to filter the list by.
     * @return Check[]
     */
    public function getChecks(array $filters = []): array
    {
        $checks = $this->checks;

        if (isset($filters['meaningful'])) {
            $is_meaningful = $filters['meaningful'];
            $checks = \array_filter($checks, fn (Check $check): bool => $is_meaningful ? $check->isMeaningful() : !$check->isMeaningful());
        }

        if (isset($filters['monitored'])) {
            $monitored = $filters['monitored'];
            $settings = $this->settings;
            $checks = \array_filter($checks, fn (string $check_id): bool => $monitored ? $settings[$check_id] : !$settings[$check_id], ARRAY_FILTER_USE_KEY);
        }

        if (isset($filters['status'])) {
            $status = $filters['status'];
            $checks = \array_filter($checks, fn (Check $check): bool => $check->getResult()->getStatus() === $status);
        }

        return $checks;
    }


    /**
     * @param bool $only_meaningful If true (default), return only meaningful checks, otherwise return all checks.
     *
     * @return AdvancedCheck[]
     */
    public function getAdvancedChecks(bool $only_meaningful = true): array
    {
        $filters = [];
        if ($only_meaningful) {
            $filters['meaningful'] = true;
        }

        return \array_filter($this->getChecks($filters), fn (Check $check): bool => $check instanceof AdvancedCheck);
    }


    /**
     * @param bool $only_meaningful If true (default), return only meaningful checks, otherwise return all checks.
     *
     * @return BasicCheck[]
     */
    public function getBasicChecks(bool $only_meaningful = true): array
    {
        $filters = [];
        if ($only_meaningful) {
            $filters['meaningful'] = true;
        }

        return \array_filter($this->getChecks($filters), fn (Check $check): bool => $check instanceof BasicCheck);
    }


    /**
     * Run all basic checks that are meaningful and are set to be monitored in non-interactive mode.
     */
    private function runBasicChecks(): void
    {
        $checks = $this->getBasicChecks();
        $issues = [];

        foreach ($checks as $check_id => $check) {
            if (!$this->settings[$check_id]) {
                // Skip checks that should not be monitored.
                continue;
            }

            // Run check.
            $result = $check->run();

            if (!$result->getStatus()) {
                $issues[$check_id] = [
                    'check' => $check,
                    'result' => $result,
                ];
            }
        }

        if ($issues !== []) {
            // Trigger an action to report found issues.
            do_action(Hooks::BASIC_CHECKS_ALERT, $issues);
        }
    }


    /**
     * Run check (asynchronously).
     */
    private function runCheck(): void
    {
        if (empty($check_id = \filter_input(INPUT_POST, 'check_id'))) {
            wp_send_json_error([
                'message' => __('No check ID provided!', 'bc-security'),
            ]);
        }

        if (($check = $this->getCheck($check_id)) === null) {
            wp_send_json_error([
                'message' => \sprintf(__('Unknown check ID: %s', 'bc-security'), $check_id),
            ]);
        }

        // Run check, grab result.
        $result = $check->run();

        wp_send_json_success([
            'timestamp' => Helper::formatLastRunTimestamp($check),
            'status' => $result->getStatus(),
            'message' => $result->getMessageAsHtml(),
        ]);
    }


    /**
     * Activate or deactivate cron jobs for advanced checks according to settings.
     */
    private function updateCronJobs(): void
    {
        foreach ($this->getAdvancedChecks(false) as $check_id => $advanced_check) {
            if ($this->settings[$check_id]) {
                $this->cron_manager->activateJob($advanced_check->getCronJobHook());
            } else {
                $this->cron_manager->deactivateJob($advanced_check->getCronJobHook());
            }
        }
    }
}
