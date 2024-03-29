<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Core\Settings;

/**
 * Every setting has a boolean value: true = monitor given check, false = do not monitor given check.
 */
class AutorunSettings extends Settings
{
    /**
     * @var array<string,bool> Default values for all settings. By default, no checks are monitored.
     */
    protected const DEFAULTS = [
        Checks\PhpFilesEditationDisabled::class => false,
        Checks\DirectoryListingDisabled::class => false,
        Checks\NoAccessToPhpFilesInUploadsDirectory::class => false,
        Checks\DisplayOfPhpErrorsIsOff::class => false,
        Checks\ErrorLogNotPubliclyAccessible::class => false,
        Checks\NoObviousUsernamesCheck::class => false,
        Checks\NoMd5HashedPasswords::class => false,
        Checks\PhpVersionSupported::class => false,
        Checks\NoPluginsRemovedFromDirectory::class => false,
        Checks\CoreIntegrity::class => false,
        Checks\PluginsIntegrity::class => false,
        Checks\SafeBrowsing::class => false,
    ];
}
