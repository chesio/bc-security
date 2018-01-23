<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

interface Jobs
{
    /** string: Hook name for "Automatic IP blacklist purging" cron job */
    const IP_BLACKLIST_CLEAN_UP = 'bc-security/ip-blacklist-clean-up';

    /** string: Hook name for "Clean logs by age" cron job */
    const LOGS_CLEAN_UP_BY_AGE = 'bc-security/logs-clean-up-by-age';

    /** string: Hook name for "Clean logs by size" cron job */
    const LOGS_CLEAN_UP_BY_SIZE = 'bc-security/logs-clean-up-by-size';

    /** string: Hook name for "Verify core checksums" cron job */
    const CORE_CHECKSUMS_VERIFIER = 'bc-security/core-checksums-verifier';

    /** string: Hook name for "Verify plugin checksums" cron job */
    const PLUGIN_CHECKSUMS_VERIFIER = 'bc-security/plugin-checksums-verifier';
}
