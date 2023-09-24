<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Cron;

interface Recurrence
{
    /**
     * @var string Built-in recurrence name for cron job that should run every hour
     */
    public const HOURLY = 'hourly';

    /**
     * @var string Built-in recurrence name for cron job that should run twice a day
     */
    public const TWICEDAILY = 'twicedaily';

    /**
     * @var string Built-in recurrence name for cron job that should run once a day
     */
    public const DAILY = 'daily';
}
