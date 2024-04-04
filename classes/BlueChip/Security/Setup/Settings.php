<?php

declare(strict_types=1);

namespace BlueChip\Security\Setup;

use BlueChip\Security\Core\Settings as CoreSettings;

/**
 * Basic settings (plugin setup)
 */
class Settings extends CoreSettings
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
     * @var array<string,string> Default values for all settings.
     */
    protected const DEFAULTS = [
        self::CONNECTION_TYPE => IpAddress::REMOTE_ADDR,
        self::GOOGLE_API_KEY => '',
    ];

    /**
     * @var array<string,callable> Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::CONNECTION_TYPE => [self::class, 'sanitizeConnectionType'],
    ];


    /**
     * Sanitize connection type. Allow only expected values.
     */
    public static function sanitizeConnectionType(string $value, string $default): string
    {
        return \array_key_exists($value, IpAddress::enlist()) ? $value : $default;
    }
}
