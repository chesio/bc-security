<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Setup;

/**
 * Basic settings (plugin setup)
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** string: What is server connection type? [REMOTE_ADDR] */
    const CONNECTION_TYPE = 'connection-type';


    /**
     * Sanitize settings array: only return known keys, provide default values for missing keys.
     *
     * @param array $s
     * @return array
     */
    public function sanitize(array $s): array
    {
        return [
            self::CONNECTION_TYPE
                => isset($s[self::CONNECTION_TYPE]) && in_array($s[self::CONNECTION_TYPE], IpAddress::enlist(), true) ? $s[self::CONNECTION_TYPE] : IpAddress::REMOTE_ADDR,
        ];
    }
}
