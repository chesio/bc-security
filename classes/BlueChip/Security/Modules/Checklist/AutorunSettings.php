<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Every setting has a boolean value: true = perform check monitoring, false = do not perform check monitoring
 */
class AutorunSettings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var string[] List of checks that can be monitored in background.
     */
    const CHECKS = [
        Checks\PhpFilesEditationDisabled::class,
        Checks\DirectoryListingDisabled::class,
        Checks\NoAccessToPhpFilesInUploadsDirectory::class,
        Checks\DisplayOfPhpErrorsIsOff::class,
        Checks\ErrorLogNotPubliclyAccessible::class,
        Checks\NoObviousUsernamesCheck::class,
        Checks\NoPluginsRemovedFromDirectory::class,
        Checks\NoMd5HashedPasswords::class,
    ];


    /**
     * @param array $s
     * @return array A hashmap with [ (string) check_id => (bool) is_monitoring_active ] values
     */
    public function sanitize(array $s): array
    {
        $check_ids = array_map(
            function (string $classname): string {
                return call_user_func([Check::class, 'getCheckId'], $classname);
            },
            self::CHECKS
        );

        return array_map(
            function (string $check_id) use ($s): bool {
                return $s[$check_id] ?? false;
            },
            array_combine($check_ids, $check_ids) // Pass check IDs as values too, so they can be used in callback.
        );
    }
}
