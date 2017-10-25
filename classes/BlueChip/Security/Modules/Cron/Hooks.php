<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

/**
 * Hooks available in cron jobs module
 */
interface Hooks
{
    /**
     * Filter: filters cron job execution time
     */
    const EXECUTION_TIME = 'bc-security/filter:cron-job-execution-time';
}
