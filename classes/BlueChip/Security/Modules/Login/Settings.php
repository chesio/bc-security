<?php

namespace BlueChip\Security\Modules\Login;

/**
 * Login security settings
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** int: Lock out for a short time after every N tries [5] */
    public const SHORT_LOCKOUT_AFTER = 'short_lockout_after';

    /** int: Lock out for a short time for this many minutes [10] */
    public const SHORT_LOCKOUT_DURATION = 'short_lockout_duration';

    /** int: Lock out for a long time after every N tries [20] */
    public const LONG_LOCKOUT_AFTER = 'long_lockout_after';

    /** int: Lock out for a long time for this many hours [24] */
    public const LONG_LOCKOUT_DURATION = 'long_lockout_duration';

    /** int: Reset failed attempts after this many days [3] */
    public const RESET_TIMEOUT = 'reset_timeout';

    /** bool: Also limit malformed/forged cookies? [Yes] */
    public const CHECK_COOKIES = 'check_cookies';

    /** array: List of usernames that trigger long lockout immediately when used to log in [empty] */
    public const USERNAME_BLACKLIST = 'username_blacklist';

    /** bool: Display generic login error message? [No] */
    public const GENERIC_LOGIN_ERROR_MESSAGE = 'display_generic_error_message';


    /**
     * @var array Default values for all settings.
     */
    protected const DEFAULTS = [
        self::SHORT_LOCKOUT_AFTER => 5,
        self::SHORT_LOCKOUT_DURATION => 10,
        self::LONG_LOCKOUT_AFTER => 20,
        self::LONG_LOCKOUT_DURATION => 24,
        self::RESET_TIMEOUT => 3,
        self::CHECK_COOKIES => true,
        self::USERNAME_BLACKLIST => [],
        self::GENERIC_LOGIN_ERROR_MESSAGE => false,
    ];

    /**
     * @var array Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::USERNAME_BLACKLIST => [self::class, 'sanitizeUsernameBlacklist'],
    ];


    /**
     * Sanitize "username blacklist" setting. Must be list of valid usernames.
     *
     * @param array|string $value
     * @return array
     */
    public static function sanitizeUsernameBlacklist($value): array
    {
        return \array_filter(self::parseList($value), '\validate_username');
    }


    /**
     * @return int Long lockout duration in seconds
     */
    public function getLongLockoutDuration(): int
    {
        return $this->data[self::LONG_LOCKOUT_DURATION] * HOUR_IN_SECONDS;
    }


    /**
     * @return int Reset timeout duration in seconds.
     */
    public function getResetTimeoutDuration(): int
    {
        return $this->data[self::RESET_TIMEOUT] * DAY_IN_SECONDS;
    }


    /**
     * @return int Short lockout duration in seconds
     */
    public function getShortLockoutDuration(): int
    {
        return $this->data[self::SHORT_LOCKOUT_DURATION] * MINUTE_IN_SECONDS;
    }


    /**
     * Get filtered list of usernames to be immediately locked out during login.
     *
     * @hook \BlueChip\Security\Modules\Login\Hooks::USERNAME_BLACKLIST
     *
     * @return array
     */
    public function getUsernameBlacklist(): array
    {
        return apply_filters(Hooks::USERNAME_BLACKLIST, $this->data[self::USERNAME_BLACKLIST]);
    }
}
