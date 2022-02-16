<?php

namespace BlueChip\Security\Setup;

/**
 * Basic settings (plugin setup)
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /**
     * @var string What is server connection type? [string:REMOTE_ADDR]
     */
    public const CONNECTION_TYPE = 'connection-type';

    /**
     * @var string Google API key (for Safe Browsing check) [string]
     */
    public const GOOGLE_API_KEY = 'google-api-key';


    /**
     * @var array Default values for all settings.
     */
    protected const DEFAULTS = [
        self::CONNECTION_TYPE => IpAddress::REMOTE_ADDR,
        self::GOOGLE_API_KEY => '',
    ];

    /**
     * @var array Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::CONNECTION_TYPE => [self::class, 'sanitizeConnectionType'],
    ];


    /**
     * Sanitize connection type. Allow only expected values.
     *
     * @param string $value
     * @param string $default
     * @return string
     */
    public static function sanitizeConnectionType(string $value, string $default): string
    {
        return \in_array($value, IpAddress::enlist(), true) ? $value : $default;
    }
}
