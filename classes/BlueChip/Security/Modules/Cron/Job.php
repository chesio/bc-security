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
     * @var int Unix timestamp (UTC) for when to run the cron job.
     */
    private $timestamp;


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
        $this->timestamp = is_int($time) ? $time : self::getTimestamp($time);
    }


    /**
     * Schedule new cron job, if not scheduled yet.
     *
     * @return bool True, if cron job has been activated or was already active, false otherwise.
     */
    public function activate()
    {
        return $this->isScheduled()
            ? true
            : (wp_schedule_event($this->timestamp, $this->recurrence, $this->hook, $this->args) !== false)
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
     * @link http://www.php.net/manual/en/datetime.formats.relative.php
     * @link https://wordpress.stackexchange.com/a/223341
     *
     * @param string $time
     * @return int
     */
    public static function getTimestamp($time)
    {
        // Get time zone from settings.
        $time_zone = new \DateTimeZone(get_option('timezone_string'));
        // Get DateTime object.
        $date = new \DateTime($time, $time_zone);
        // Get timestamp.
        return $date->getTimestamp();
    }
}
