<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

/**
 * All setting keys are booleans and indicate whether particular cron job is active (~ should be scheduled as soon as
 * plugin is active).
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            Jobs::CHECKLIST_CHECK => true,
            Jobs::IP_BLACKLIST_CLEAN_UP => true,
            Jobs::LOGS_CLEAN_UP_BY_AGE => true,
            Jobs::LOGS_CLEAN_UP_BY_SIZE => true,
            Jobs::CORE_INTEGRITY_CHECK => false,
            Jobs::PLUGINS_INTEGRITY_CHECK => false,
            Jobs::NO_REMOVED_PLUGINS_CHECK => false,
        ];
    }
}
