<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

use \BlueChip\Security\Modules;

/**
 * Simple wrapper for cron job handling
 */
class Job implements Modules\Activable, Modules\Initializable
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
     * @var string How often the cron job should recur.
     */
    private $recurrence;

    /**
     * @var int|string Unix timestamp or time string indicating when to run the cron job.
     */
    private $time;


    /**
     * @param int|string $time
     * @param string $recurrence
     * @param string $hook
     * @param callable $action
     * @param array $args
     */
    public function __construct($time, $recurrence, $hook, callable $action, array $args = [])
    {
        $this->action = $action;
        $this->args = $args;
        $this->hook = $hook;
        $this->recurrence = $recurrence;
        $this->time = $time;
    }


    /**
     * Schedule new cron job, if not scheduled yet.
     *
     * @hook \BlueChip\Security\Modules\Cron\Hooks::EXECUTION_TIME
     *
     * @return bool True, if cron job has been activated or was already active, false otherwise.
     */
    public function activate()
    {
        // Filter $time value.
        $time = apply_filters(Hooks::EXECUTION_TIME, $this->time, $this->hook);
        // Compute Unix timestamp (UTC) for when to run the cron job based on $time value.
        $timestamp = is_int($time) ? $time : self::getTimestamp($time);

        return $this->isScheduled()
            ? true
            : (wp_schedule_event($timestamp, $this->recurrence, $this->hook, $this->args) !== false)
        ;
    }


    /**
     * Unschedule all cron jobs.
     */
    public function deactivate()
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
     * @return bool True, if cron job is currently scheduled.
     */
    public function isScheduled()
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
    public static function getTimestamp($time_string)
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
        // Get time zone from settings.
        $time_zone = new \DateTimeZone(get_option('timezone_string'));
        // Get DateTime object.
        $date = new \DateTime($time, $time_zone);
        // Get timestamp.
        return $date->getTimestamp();
    }
}
