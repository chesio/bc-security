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
        Checks\NoMd5HashedPasswords::class,
        Checks\NoPluginsRemovedFromDirectory::class,
        Checks\CoreIntegrity::class,
        Checks\PluginsIntegrity::class,
    ];


    /**
     * By default, no checks are monitored.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return array_fill_keys(array_map([Check::class, 'getCheckId'], self::CHECKS), false);
    }
}
