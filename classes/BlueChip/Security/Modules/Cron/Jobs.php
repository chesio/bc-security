<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Cron;

abstract class Jobs
{
    /** string: Hook name for "Checklist autorun" cron job */
    public const CHECKLIST_CHECK = 'bc-security/checklist-autorun';

    /** string: Hook name for "External blocklist refresh" cron job */
    public const EXTERNAL_BLOCKLIST_REFRESH = 'bc-security/external-blocklist-refresh';

    /** string: Hook name for "Failed logins table clean up" cron job */
    public const FAILED_LOGINS_CLEAN_UP = 'bc-security/failed-logins-clean-up';

    /** string: Hook name for "Automatic internal blocklist purging" cron job */
    public const INTERNAL_BLOCKLIST_CLEAN_UP = 'bc-security/internal-blocklist-clean-up';

    /** string: Hook name for "Clean logs by age" cron job */
    public const LOGS_CLEAN_UP_BY_AGE = 'bc-security/logs-clean-up-by-age';

    /** string: Hook name for "Clean logs by size" cron job */
    public const LOGS_CLEAN_UP_BY_SIZE = 'bc-security/logs-clean-up-by-size';

    /** string: Hook name for "WordPress core files are untouched" check monitor */
    public const CORE_INTEGRITY_CHECK = 'bc-security/core-integrity-check';

    /** string: Hook name for "Plugin files are untouched" check monitor */
    public const PLUGINS_INTEGRITY_CHECK = 'bc-security/plugin-integrity-check';

    /** string: Hook name for "No plugins removed from WordPress.org installed" check monitor */
    public const NO_REMOVED_PLUGINS_CHECK = 'bc-security/no-removed-plugins-check';

    /** string: Hook name for "Site is not blacklisted by Google" check monitor */
    public const SAFE_BROWSING_CHECK = 'bc-security/safe-browsing-check';

    /**
     * @return array<string,string> List of all implemented cron jobs.
     */
    public static function enlist(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }
}
