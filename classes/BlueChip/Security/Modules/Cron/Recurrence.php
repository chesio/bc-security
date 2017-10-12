<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Cron;

interface Recurrence
{
    /**
     * @var string Built-in recurrence name for cron job that should run every hour
     */
    const HOURLY = 'hourly';

    /**
     * @var string Built-in recurrence name for cron job that should run twice a day
     */
    const TWICEDAILY = 'twicedaily';

    /**
     * @var string Built-in recurrence name for cron job that should run once a day
     */
    const DAILY = 'daily';
}
