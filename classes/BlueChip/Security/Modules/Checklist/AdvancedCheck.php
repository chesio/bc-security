<?php

namespace BlueChip\Security\Modules\Checklist;

/**
 * Base class for advanced checks.
 *
 * Every advanced check depend on data from external resources and therefore is run from within separate execution
 * thread, ie:
 * - AJAX request when run interactively
 * - cron job when run non-interactively
 */
abstract class AdvancedCheck extends Check
{
    /**
     * @var string
     */
    protected const CRON_JOB_HOOK = '';

    /**
     * @return string Hook of cron job this check is bind to.
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
    public function runInCron(): void
    {
        $result = $this->run();

        if ($result->getStatus() !== true) {
            do_action(Hooks::ADVANCED_CHECK_ALERT, $this, $result);
        }
    }
}
