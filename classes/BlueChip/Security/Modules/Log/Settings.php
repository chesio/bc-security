<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

/**
 * Log module settings
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** int: Maximum size of log table in thousands of records [20] */
    const LOG_MAX_SIZE = 'log_max_size';

    /** int: Maximum age of log record in days [365] */
    const LOG_MAX_AGE = 'log_max_age';


    /**
     * Sanitize settings array: only return known keys, provide default values for missing keys.
     *
     * @param array $s
     * @return array
     */
    public function sanitize(array $s): array
    {
        return [
            self::LOG_MAX_SIZE
                => isset($s[self::LOG_MAX_SIZE]) ? intval($s[self::LOG_MAX_SIZE]) : 20,
            self::LOG_MAX_AGE
                => isset($s[self::LOG_MAX_AGE]) ? intval($s[self::LOG_MAX_AGE]) : 365,
        ];
    }


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
