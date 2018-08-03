<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

/**
 * Settings indicate whether particular cron job is active. Only active jobs are scheduled on plugin activation.
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var array Default values for all settings.
     */
    const DEFAULTS = [
        Jobs::CHECKLIST_CHECK => true,
        Jobs::IP_BLACKLIST_CLEAN_UP => true,
        Jobs::LOGS_CLEAN_UP_BY_AGE => true,
        Jobs::LOGS_CLEAN_UP_BY_SIZE => true,
        Jobs::CORE_INTEGRITY_CHECK => false,
        Jobs::PLUGINS_INTEGRITY_CHECK => false,
        Jobs::NO_REMOVED_PLUGINS_CHECK => false,
    ];
}
