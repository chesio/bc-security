<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

use BlueChip\Security\Modules;

/**
 * Cron job factory
 */
class Manager implements Modules\Activable
{
    /**
     * @var \BlueChip\Security\Modules\Cron\Job[] Cron jobs
     */
    private $jobs = [];

    /**
     * @var \BlueChip\Security\Modules\Cron\Settings Module settings
     */
    private $settings;


    /**
     * @param \BlueChip\Security\Modules\Cron\Settings $settings
     */
    public function __construct(Settings $settings)
    {
        // In the moment, all cron jobs can be scheduled in the same way (at night with daily recurrence).
        foreach (Jobs::enlist() as $hook) {
            $this->jobs[$hook] = new Job($hook, Job::RUN_AT_NIGHT, Recurrence::DAILY);
        }
        $this->settings = $settings;
    }


    public function activate()
    {
        // Schedule cron jobs that are active.
        foreach ($this->jobs as $hook => $job) {
            if ($this->settings[$hook]) {
                $job->schedule();
            }
        }
    }


    public function deactivate()
    {
        // Unschedule all scheduled cron jobs.
        foreach ($this->jobs as $job) {
            if ($job->isScheduled()) {
                $job->unschedule();
            }
        }
    }


    /**
     * @param string $hook
     * @return \BlueChip\Security\Modules\Cron\Job
     */
    public function getJob(string $hook): Job
    {
        return $this->jobs[$hook];
    }


    /**
     * Activate cron job: schedule the job and mark it as permanently active, if scheduling succeeds.
     *
     * @param string $hook
     * @return bool True, if cron job has been activated or was already active, false otherwise.
     */
    public function activateJob(string $hook)
    {
        if ($this->getJob($hook)->schedule()) {
            $this->settings[$hook] = true;
        }

        return $this->settings[$hook] === true;
    }


    /**
     * Deactivate cron job: unschedule the job and mark it as permanently inactive, if unscheduling succeeds.
     *
     * @param string $hook
     * @return bool True, if cron job has been deactivated or was not active already, false otherwise.
     */
    public function deactivateJob(string $hook)
    {
        if ($this->getJob($hook)->unschedule()) {
            $this->settings[$hook] = false;
        }

        return $this->settings[$hook] === false;
    }
}
