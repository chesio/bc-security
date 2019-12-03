<?php

namespace BlueChip\Security\Setup;

/**
 * Basic settings (plugin setup)
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** string: What is server connection type? [REMOTE_ADDR] */
    const CONNECTION_TYPE = 'connection-type';

    /**
     * @var array Default values for all settings.
     */
    const DEFAULTS = [
        self::CONNECTION_TYPE => IpAddress::REMOTE_ADDR,
    ];

    /**
     * @var array Custom sanitizers.
     */
    const SANITIZERS = [
        self::CONNECTION_TYPE => [self::class, 'sanitizeConnectionType'],
    ];


    /**
     * Sanitize connection type. Allow only expected values.
     *
     * @param string $value
     * @return string
     */
    public static function sanitizeConnectionType(string $value): string
    {
        return \in_array($value, IpAddress::enlist(), true) ? $value : self::DEFAULTS[self::CONNECTION_TYPE];
    }
}
