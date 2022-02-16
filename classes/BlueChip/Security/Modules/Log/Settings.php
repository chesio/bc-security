<?php

namespace BlueChip\Security\Modules\Log;

/**
 * Log module settings
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** @var string Maximum size of log table in thousands of records [int:20] */
    public const LOG_MAX_SIZE = 'log_max_size';

    /** @var string Maximum age of log record in days [int:365] */
    public const LOG_MAX_AGE = 'log_max_age';


    /**
     * @var array Default values for all settings.
     */
    protected const DEFAULTS = [
        self::LOG_MAX_SIZE => 20,
        self::LOG_MAX_AGE => 365,
    ];


    /**
     * @return int Maximum age of log records in seconds.
     */
    public function getMaxAge(): int
    {
        return $this->data[self::LOG_MAX_AGE] * DAY_IN_SECONDS;
    }


    /**
     * @return int Maximum size of log table in number of records.
     */
    public function getMaxSize(): int
    {
        return $this->data[self::LOG_MAX_SIZE] * 1000;
    }
}
