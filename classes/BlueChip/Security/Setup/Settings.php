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


    public function getDefaults(): array
    {
        return [
            self::CONNECTION_TYPE => IpAddress::REMOTE_ADDR,
        ];
    }


    public function sanitizeSingleValue(string $key, $value, $default)
    {
        switch ($key) {
            case self::CONNECTION_TYPE:
                return in_array($value, IpAddress::enlist(), true) ? $value : $default;
            default:
                return parent::sanitizeSingleValue($key, $value, $default);
        }
    }
}
