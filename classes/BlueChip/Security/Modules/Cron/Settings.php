<?php

namespace BlueChip\Security\Modules\Cron;

use BlueChip\Security\Core\Settings as CoreSettings;

/**
 * Settings indicate whether particular cron job is active. Only active jobs are scheduled on plugin activation.
 */
class Settings extends CoreSettings
{
    /**
     * @var array<string,bool> Default values for all settings.
     */
    protected const DEFAULTS = [
        Jobs::CHECKLIST_CHECK => true,
        Jobs::EXTERNAL_BLOCKLIST_REFRESH => false,
        Jobs::INTERNAL_BLOCKLIST_CLEAN_UP => true,
        Jobs::LOGS_CLEAN_UP_BY_AGE => true,
        Jobs::LOGS_CLEAN_UP_BY_SIZE => true,
        Jobs::CORE_INTEGRITY_CHECK => false,
        Jobs::PLUGINS_INTEGRITY_CHECK => false,
        Jobs::NO_REMOVED_PLUGINS_CHECK => false,
        Jobs::SAFE_BROWSING_CHECK => false,
    ];
}
