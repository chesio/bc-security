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
            Jobs::CHECKLIST_CHECK
                => isset($s[Jobs::CHECKLIST_CHECK]) ? boolval($s[Jobs::CHECKLIST_CHECK]) : true,
            Jobs::IP_BLACKLIST_CLEAN_UP
                => isset($s[Jobs::IP_BLACKLIST_CLEAN_UP]) ? boolval($s[Jobs::IP_BLACKLIST_CLEAN_UP]) : true,
            Jobs::LOGS_CLEAN_UP_BY_AGE
                => isset($s[Jobs::LOGS_CLEAN_UP_BY_AGE]) ? boolval($s[Jobs::LOGS_CLEAN_UP_BY_AGE]) : true,
            Jobs::LOGS_CLEAN_UP_BY_SIZE
                => isset($s[Jobs::LOGS_CLEAN_UP_BY_SIZE]) ? boolval($s[Jobs::LOGS_CLEAN_UP_BY_SIZE]) : true,
            Jobs::CORE_INTEGRITY_CHECK
                => isset($s[Jobs::CORE_INTEGRITY_CHECK]) ? boolval($s[Jobs::CORE_INTEGRITY_CHECK]) : false,
            Jobs::PLUGINS_INTEGRITY_CHECK
                => isset($s[Jobs::PLUGINS_INTEGRITY_CHECK]) ? boolval($s[Jobs::PLUGINS_INTEGRITY_CHECK]) : false,
            Jobs::NO_REMOVED_PLUGINS_CHECK
                => isset($s[Jobs::NO_REMOVED_PLUGINS_CHECK]) ? boolval($s[Jobs::NO_REMOVED_PLUGINS_CHECK]) : false,
        ];
    }
}
