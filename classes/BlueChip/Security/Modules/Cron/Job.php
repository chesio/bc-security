<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

/**
 * Simple wrapper for cron job handling
 */
class Job
{
    /**
     * @var string Indicate that cron job should be scheduled at random time between 00:00:00 and 05:59:59 local time.
     */
    const RUN_AT_NIGHT = 'run_at_night';

    /**
     * @var string Indicate that cron job should be scheduled at random time during entire day.
     */
    const RUN_RANDOMLY = 'run_randomly';


    /**
     * @var callback Callback to be executed on cron job run.
     */
    private $action;

    /**
     * @var array Arguments to pass to the hook's callback function.
     */
    private $args;

    /**
     * @var string Action hook to execute when cron job is run.
     */
    private $hook;

    /**
     * @var \BlueChip\Security\Modules\Cron\Settings
     */
    private $settings;

    /**
     * @var string How often the cron job should recur.
     */
    private $recurrence;

    /**
     * @var int|string Unix timestamp or time string indicating when to run the cron job.
     */
    private $time;


    /**
     * @param \BlueChip\Security\Modules\Cron\Settings $settings
     * @param int|string $time
     * @param string $recurrence
     * @param string $hook
     * @param callable $action
     * @param array $args
     */
    public function __construct(Settings $settings, $time, string $recurrence, string $hook, callable $action, array $args = [])
    {
        $this->action = $action;
        $this->args = $args;
        $this->hook = $hook;
        $this->settings = $settings;
        $this->recurrence = $recurrence;
        $this->time = $time;
    }


    /**
     * Activate this cron job: schedule and mark the job as permanently active.
     *
     * @return bool True, if cron job has been activated or was already active, false otherwise.
     */
    public function activate(): bool
    {
        $this->settings[$this->hook] = true;
        return $this->schedule();
    }


    /**
     * Deactivate this cron job: unschedule and mark the job as permanently inactive.
     */
    public function deactivate()
    {
        $this->settings[$this->hook] = false;
        $this->unschedule();
    }


    /**
     * Schedule this cron job, if not scheduled yet.
     *
     * @return bool True, if cron job has been activated or was already active, false otherwise.
     */
    public function schedule(): bool
    {
        if ($this->isScheduled()) {
            // Ok, job done - that was easy!
            return true;
        }

        // Compute Unix timestamp (UTC) for when to run the cron job based on $time value.
        $timestamp = is_int($this->time) ? $this->time : self::getTimestamp($this->time);

        return wp_schedule_event($timestamp, $this->recurrence, $this->hook, $this->args) !== false;
    }


    /**
     * Unschedule this cron job.
     */
    public function unschedule()
    {
        wp_clear_scheduled_hook($this->hook, $this->args);
    }


    /**
     * Add action into registered cron job hook.
     */
    public function init()
    {
        add_action($this->hook, $this->action);
    }


    /**
     * @return bool True, if cron job should be scheduled, when plugin is active, false otherwise.
     */
    public function isOn(): bool
    {
        return $this->settings[$this->hook];
    }


    /**
     * @return bool True, if cron job is currently scheduled.
     */
    public function isScheduled(): bool
    {
        return is_int(wp_next_scheduled($this->hook, $this->args));
    }


    /**
     * Return timestamp for given $time string offset for current WP time zone.
     *
     * Note: $time can be also one of self::RUN_AT_NIGHT or self::RUN_RANDOMLY constants.
     *
     * @link http://www.php.net/manual/en/datetime.formats.relative.php
     * @link https://wordpress.stackexchange.com/a/223341
     *
     * @param string $time_string
     * @return int
     */
    public static function getTimestamp(string $time_string): int
    {
        if ($time_string === self::RUN_AT_NIGHT || $time_string === self::RUN_RANDOMLY) {
            $hour = mt_rand(0, ($time_string === self::RUN_AT_NIGHT) ? 5 : 23);
            $minute = mt_rand(0, 59);
            $second = mt_rand(0, 59);
            $time = sprintf("%02d:%02d:%02d", $hour, $minute, $second);
        } else {
            // Assume $time_string denotes actual time like '01:02:03'.
            $time = $time_string;
        }
        // Get time zone from settings. Fall back to UTC, if option is empty.
        $time_zone = new \DateTimeZone(get_option('timezone_string') ?: 'UTC');
        // Get DateTime object.
        $date = new \DateTime($time, $time_zone);
        // Get timestamp.
        return $date->getTimestamp();
    }
}
