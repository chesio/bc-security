<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

abstract class Jobs
{
    /** string: Hook name for "Checklist autorun" cron job */
    const CHECKLIST_CHECK = 'bc-security/checklist-autorun';

    /** string: Hook name for "Automatic IP blacklist purging" cron job */
    const IP_BLACKLIST_CLEAN_UP = 'bc-security/ip-blacklist-clean-up';

    /** string: Hook name for "Clean logs by age" cron job */
    const LOGS_CLEAN_UP_BY_AGE = 'bc-security/logs-clean-up-by-age';

    /** string: Hook name for "Clean logs by size" cron job */
    const LOGS_CLEAN_UP_BY_SIZE = 'bc-security/logs-clean-up-by-size';

    /** string: Hook name for "WordPress core files are untouched" check monitor */
    const CORE_INTEGRITY_CHECK = 'bc-security/core-integrity-check';

    /** string: Hook name for "Plugin files are untouched" check monitor */
    const PLUGINS_INTEGRITY_CHECK = 'bc-security/plugin-integrity-check';

    /** string: Hook name for "No plugins removed from WordPress.org installed" check monitor */
    const NO_REMOVED_PLUGINS_CHECK = 'bc-security/no-removed-plugins-check';


    /**
     * @return array List of all implemented cron jobs.
     */
    public static function enlist(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }
}
