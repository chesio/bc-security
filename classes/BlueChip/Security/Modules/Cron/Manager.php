<?php

namespace BlueChip\Security\Modules\Cron;

use BlueChip\Security\Modules;

/**
 * Cron job factory
 */
class Manager implements Modules\Activable
{
    /**
     * @var Job[] Cron jobs
     */
    private array $jobs = [];


    /**
     * @param Settings $settings Module settings
     */
    public function __construct(private Settings $settings)
    {
        // In the moment, all cron jobs can be scheduled in the same way (at night with daily recurrence).
        foreach (Jobs::enlist() as $hook) {
            $this->jobs[$hook] = new Job($hook, Job::RUN_AT_NIGHT, Recurrence::DAILY);
        }
    }


    public function activate(): void
    {
        // Schedule cron jobs that are active.
        foreach ($this->jobs as $hook => $job) {
            if ($this->settings[$hook]) {
                $job->schedule();
            }
        }
    }


    public function deactivate(): void
    {
        // Unschedule all scheduled cron jobs.
        foreach ($this->jobs as $job) {
            if ($job->isScheduled()) {
                $job->unschedule();
            }
        }
    }


    public function getJob(string $hook): Job
    {
        return $this->jobs[$hook];
    }


    /**
     * Activate cron job: schedule the job and mark it as permanently active if scheduling succeeds.
     *
     * @param string $hook
     *
     * @return bool True if cron job has been activated or was active already, false otherwise.
     */
    public function activateJob(string $hook): bool
    {
        if ($this->getJob($hook)->schedule()) {
            $this->settings[$hook] = true;
        }

        return $this->settings[$hook] === true;
    }


    /**
     * Deactivate cron job: unschedule the job and mark it as permanently inactive if unscheduling succeeds.
     *
     * @param string $hook
     *
     * @return bool True if cron job has been deactivated or was inactive already, false otherwise.
     */
    public function deactivateJob(string $hook): bool
    {
        if ($this->getJob($hook)->unschedule()) {
            $this->settings[$hook] = false;
        }

        return $this->settings[$hook] === false;
    }
}
