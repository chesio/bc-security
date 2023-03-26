<?php

namespace BlueChip\Security\Modules\Cron;

/**
 * Settings indicate whether particular cron job is active. Only active jobs are scheduled on plugin activation.
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var array<string,bool> Default values for all settings.
     */
    protected const DEFAULTS = [
        Jobs::AWS_IP_PREFIXES_REFRESH => false,
        Jobs::CHECKLIST_CHECK => true,
        Jobs::INTERNAL_BLOCKLIST_CLEAN_UP => true,
        Jobs::LOGS_CLEAN_UP_BY_AGE => true,
        Jobs::LOGS_CLEAN_UP_BY_SIZE => true,
        Jobs::CORE_INTEGRITY_CHECK => false,
        Jobs::PLUGINS_INTEGRITY_CHECK => false,
        Jobs::NO_REMOVED_PLUGINS_CHECK => false,
        Jobs::SAFE_BROWSING_CHECK => false,
    ];
}
