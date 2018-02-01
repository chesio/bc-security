<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

abstract class Checks
{
    /**
     * @var \BlueChip\Security\Modules\Checklist\Check[]
     */
    private static $checks;


    /**
     * Return list of all implemented checks.
     *
     * @param \wpdb $wpdb WordPress database access abstraction object
     * @return \BlueChip\Security\Modules\Checklist\Check[]
     */
    public static function getAll(\wpdb $wpdb): array
    {
        if (empty(self::$checks)) {
            self::$checks = [
                // PHP files editation should be off.
                new Checks\PhpFilesEditationDisabled(),

                // Directory listings should be disabled.
                new Checks\DirectoryListingDisabled(),

                // PHP files in uploads directory should not be accessible on frontend.
                new Checks\NoAccessToPhpFilesInUploadsDirectory(),

                // Display of erros should be off in production environment.
                new Checks\DisplayOfPhpErrorsIsOff(),

                // Error log should not be publicly visible, if debugging is on.
                new Checks\ErrorLogNotPubliclyAccessible(),

                // There should be no obvious usernames.
                new Checks\NoObviousUsernamesCheck(),

                // No passwords should be hashed with (default) MD5 hash.
                new Checks\NoMd5HashedPasswords($wpdb),
            ];
        }

        return self::$checks;
    }
}
