<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Helpers\AjaxHelper;
use BlueChip\Security\Modules;

class Manager implements Modules\Initializable
{
    /**
     * @var string
     */
    const ASYNC_CHECK_ACTION = 'bc_security_run_check';

    /**
     * @var \BlueChip\Security\Modules\Checklist\AutorunSettings
     */
    private $settings;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * @param \BlueChip\Security\Modules\Checklist\AutorunSettings $settings
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(AutorunSettings $settings, \wpdb $wpdb)
    {
        $this->settings = $settings;
        $this->wpdb = $wpdb;
    }


    public function init()
    {
        // Hook into cron job execution.
        add_action(Modules\Cron\Jobs::CHECKLIST_CHECK, [$this, 'runChecks'], 10, 0);
        // Register AJAX handler.
        AjaxHelper::addHandler(self::ASYNC_CHECK_ACTION, [$this, 'runCheck']);
    }


    /**
     * Return list of all implemented checks, optionally filtered.
     *
     * @param bool $meaningful_only If true, only checks that make sense in current context are returned.
     * @param string $class If given, only checks of that class are returned.
     * @return \BlueChip\Security\Modules\Checklist\Check[]
     */
    public function getChecks(bool $meaningful_only = false, string $class = ''): array
    {
        $checks = [
            // PHP files editation should be off.
            Checks\PhpFilesEditationDisabled::getId() => new Checks\PhpFilesEditationDisabled(),

            // Directory listings should be disabled.
            Checks\DirectoryListingDisabled::getId() => new Checks\DirectoryListingDisabled(),

            // PHP files in uploads directory should not be accessible on frontend.
            Checks\NoAccessToPhpFilesInUploadsDirectory::getId() => new Checks\NoAccessToPhpFilesInUploadsDirectory(),

            // Display of erros should be off in production environment.
            Checks\DisplayOfPhpErrorsIsOff::getId() => new Checks\DisplayOfPhpErrorsIsOff(),

            // Error log should not be publicly visible, if debugging is on.
            Checks\ErrorLogNotPubliclyAccessible::getId() => new Checks\ErrorLogNotPubliclyAccessible(),

            // There should be no obvious usernames.
            Checks\NoObviousUsernamesCheck::getId() => new Checks\NoObviousUsernamesCheck(),

            // There are no plugins installed that have been removed from plugins directory.
            Checks\NoPluginsRemovedFromDirectory::getId() => new Checks\NoPluginsRemovedFromDirectory(),

            // No passwords should be hashed with (default) MD5 hash.
            Checks\NoMd5HashedPasswords::getId() => new Checks\NoMd5HashedPasswords($this->wpdb),
        ];

        if (!empty($class)) {
            $checks = array_filter($checks, function (Check $check) use ($class): bool {
                return $check instanceof $class;
            });
        }

        if ($meaningful_only) {
            $checks = array_filter($checks, function (Check $check): bool {
                return $check->makesSense();
            });
        }

        return $checks;
    }


    /**
     * Run all checks that make sense in current context and are set to be monitored in non-interactive mode.
     *
     * @internal Method is intended to be run from within cron requests.
     */
    public function runChecks()
    {
        $checks = $this->getChecks(true);
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

        if (!empty($issues)) {
            // Trigger an action to report found issues.
            do_action(Hooks::CHECK_ALERT, $issues);
        }
    }


    /**
     * Run check (asynchronously).
     *
     * @internal Method is intended to be run from within AJAX requests.
     */
    public function runCheck()
    {
        if (empty($check_id = filter_input(INPUT_POST, 'check_id', FILTER_SANITIZE_STRING))) {
            wp_send_json_error([
                'message' => __('No check ID provided!', 'bc-security'),
            ]);
        }

        $checks = $this->getChecks();
        if (!isset($checks[$check_id])) {
            wp_send_json_error([
                'message' => sprintf(__('Unknown check ID: %s', 'bc-security'), $check_id),
            ]);
        }

        // Run check.
        $result = $checks[$check_id]->run();

        wp_send_json_success([
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
        ]);
    }
}
