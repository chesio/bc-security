<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Login;

/**
 * Login security settings
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** int: Lock out for a short time after every N tries [5] */
    const SHORT_LOCKOUT_AFTER = 'short_lockout_after';

    /** int: Lock out for a short time for this many minutes [10] */
    const SHORT_LOCKOUT_DURATION = 'short_lockout_duration';

    /** int: Lock out for a long time after every N tries [20] */
    const LONG_LOCKOUT_AFTER = 'long_lockout_after';

    /** int: Lock out for a long time for this many hours [24] */
    const LONG_LOCKOUT_DURATION = 'long_lockout_duration';

    /** int: Reset failed attempts after this many days [3] */
    const RESET_TIMEOUT = 'reset_timeout';

    /** bool: Also limit malformed/forged cookies? [Yes] */
    const CHECK_COOKIES = 'check_cookies';

    /** array: List of usernames that trigger long lockout immediately when used to log in [empty] */
    const USERNAME_BLACKLIST = 'username_blacklist';

    /** bool: Display generic login error message? [No] */
    const GENERIC_LOGIN_ERROR_MESSAGE = 'display_generic_error_message';


    /**
     * Sanitize settings array: only return known keys, provide default values
     * for missing keys.
     *
     * @param array $s
     * @return array
     */
    public function sanitize($s)
    {
        return [
            self::SHORT_LOCKOUT_AFTER
                => isset($s[self::SHORT_LOCKOUT_AFTER]) ? intval($s[self::SHORT_LOCKOUT_AFTER]) : 5,
            self::SHORT_LOCKOUT_DURATION
                => isset($s[self::SHORT_LOCKOUT_DURATION]) ? intval($s[self::SHORT_LOCKOUT_DURATION]) : 10,
            self::LONG_LOCKOUT_AFTER
                => isset($s[self::LONG_LOCKOUT_AFTER]) ? intval($s[self::LONG_LOCKOUT_AFTER]) : 20,
            self::LONG_LOCKOUT_DURATION
                => isset($s[self::LONG_LOCKOUT_DURATION]) ? intval($s[self::LONG_LOCKOUT_DURATION]) : 24,
            self::RESET_TIMEOUT
                => isset($s[self::RESET_TIMEOUT]) ? intval($s[self::RESET_TIMEOUT]) : 3,
            self::CHECK_COOKIES
                => isset($s[self::CHECK_COOKIES]) ? boolval($s[self::CHECK_COOKIES]) : true,
            self::USERNAME_BLACKLIST
                => isset($s[self::USERNAME_BLACKLIST]) ? array_filter($this->parseList($s[self::USERNAME_BLACKLIST]), '\validate_username') : [],
            self::GENERIC_LOGIN_ERROR_MESSAGE
                => isset($s[self::GENERIC_LOGIN_ERROR_MESSAGE]) ? boolval($s[self::GENERIC_LOGIN_ERROR_MESSAGE]) : false,
        ];
    }


    /**
     * @return int Long lockout duration in seconds
     */
    public function getLongLockoutDuration()
    {
        return $this->data[self::LONG_LOCKOUT_DURATION] * HOUR_IN_SECONDS;
    }


    /**
     * @return int Reset timeout duration in seconds.
     */
    public function getResetTimeoutDuration()
    {
        return $this->data[self::RESET_TIMEOUT] * DAY_IN_SECONDS;
    }


    /**
     * @return int Short lockout duration in seconds
     */
    public function getShortLockoutDuration()
    {
        return $this->data[self::SHORT_LOCKOUT_DURATION] * MINUTE_IN_SECONDS;
    }


    /**
     * Get filtered list of usernames to be immediately locked out during login.
     *
     * @hook bc_security_login_username_blacklist
     *
     * @return array
     */
    public function getUsernameBlacklist()
    {
        return apply_filters(Hooks::USERNAME_BLACKLIST, $this->data[self::USERNAME_BLACKLIST]);
    }
}
