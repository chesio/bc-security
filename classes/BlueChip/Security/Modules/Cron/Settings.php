<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

/**
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * Sanitize settings array: only return known keys, provide default values for missing keys.
     *
     * All setting keys are booleans and indicate whether particular cron job is active (~ should be scheduled as soon
     * as plugin is active).
     *
     * @param array $s
     * @return array
     */
    public function sanitize(array $s): array
    {
        return [
            Jobs::IP_BLACKLIST_CLEAN_UP
                => isset($s[Jobs::IP_BLACKLIST_CLEAN_UP]) ? boolval($s[Jobs::IP_BLACKLIST_CLEAN_UP]) : true,
            Jobs::LOGS_CLEAN_UP_BY_AGE
                => isset($s[Jobs::LOGS_CLEAN_UP_BY_AGE]) ? boolval($s[Jobs::LOGS_CLEAN_UP_BY_AGE]) : true,
            Jobs::LOGS_CLEAN_UP_BY_SIZE
                => isset($s[Jobs::LOGS_CLEAN_UP_BY_SIZE]) ? boolval($s[Jobs::LOGS_CLEAN_UP_BY_SIZE]) : true,
            Jobs::CORE_CHECKSUMS_VERIFIER
                => isset($s[Jobs::CORE_CHECKSUMS_VERIFIER]) ? boolval($s[Jobs::CORE_CHECKSUMS_VERIFIER]) : true,
            Jobs::PLUGIN_CHECKSUMS_VERIFIER
                => isset($s[Jobs::PLUGIN_CHECKSUMS_VERIFIER]) ? boolval($s[Jobs::PLUGIN_CHECKSUMS_VERIFIER]) : true,
        ];
    }
}
