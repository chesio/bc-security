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
    /** int: Maximum size of log table [20000] */
    const LOG_MAX_SIZE = 'log_max_size';

    /** int: Maximum age of log record in days [365] */
    const LOG_MAX_AGE = 'log_max_age';


    /**
     * Sanitize settings array: only return known keys, provide default values
     * for missing keys.
     *
     * @param array $s
     * @return array
     */
    public function sanitize(array $s)
    {
        return [
            self::LOG_MAX_SIZE
                => isset($s[self::LOG_MAX_SIZE]) ? intval($s[self::LOG_MAX_SIZE]) : 20000,
            self::LOG_MAX_AGE
                => isset($s[self::LOG_MAX_AGE]) ? intval($s[self::LOG_MAX_AGE]) : 365,
        ];
    }
}
