<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Base class for advanced checks. Advanced checks depend on data from external resources.
 */
abstract class AdvancedCheck extends Check
{
    /**
     * @var string
     */
    const CHECK_CLASS = 'advanced';


    /**
     * @return string Hook of cron job performing the check.
     */
    public function getCronJobHook(): string
    {
        return static::CRON_JOB_HOOK;
    }


    /**
     * Run the check from within cron job.
     *
     * @hook \BlueChip\Security\Modules\Checklist\Hooks::ADVANCED_CHECK_ALERT
     */
    public function runInCron()
    {
        $result = $this->run();

        if ($result->getStatus() !== true) {
            do_action(Checklist\Hooks::ADVANCED_CHECK_ALERT, $this, $result);
        }
    }
}
