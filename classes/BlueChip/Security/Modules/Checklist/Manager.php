<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Modules;

class Manager implements Modules\Initializable
{
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
    }


    /**
     * @return array List of IDs of all implemented checks.
     */
    public static function getIds(): array
    {
        return [
                Checks\PhpFilesEditationDisabled::getId(),
                Checks\DirectoryListingDisabled::getId(),
                Checks\NoAccessToPhpFilesInUploadsDirectory::getId(),
                Checks\DisplayOfPhpErrorsIsOff::getId(),
                Checks\ErrorLogNotPubliclyAccessible::getId(),
                Checks\NoObviousUsernamesCheck::getId(),
                Checks\NoPluginsRemovedFromDirectory::getId(),
                Checks\NoMd5HashedPasswords::getId(),
        ];
    }


    /**
     * Return list of all implemented checks.
     *
     * @return \BlueChip\Security\Modules\Checklist\Check[]
     */
    public function getChecks(): array
    {
        return [
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
    }


    /**
     * Run all checks that are not disabled in settings and make sense in current context.
     */
    public function runChecks()
    {
        $checks = $this->getChecks();
        $issues = [];

        foreach ($checks as $check_id => $check) {
            if (!$this->settings[$check_id] || !$check->makesSense()) {
                // Skip checks that should not be monitored and checks that don't make sense in current context.
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
}
