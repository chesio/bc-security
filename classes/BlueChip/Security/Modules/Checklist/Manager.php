<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

class Manager
{
    /**
     * @var \BlueChip\Security\Modules\Checklist\Settings
     */
    private $settings;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * @param \BlueChip\Security\Modules\Checklist\Settings $settings
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(Settings $settings, \wpdb $wpdb)
    {
        $this->settings = $settings;
        $this->wpdb = $wpdb;
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
        $disabled = $this->settings[Settings::DISABLED_CHECKS];
        $issues = [];

        foreach ($checks as $check_id => $check) {
            if ($disabled[$check_id] || !$check->makesSense()) {
                // Skip disabled checks and checks that don't make sense in current context.
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
