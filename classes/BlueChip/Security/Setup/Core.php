<?php

namespace BlueChip\Security\Setup;

class Core
{
    /**
     * @var string Connection type (see \BlueChip\Security\Setup\IpAddress)
     */
    private string $connection_type = IpAddress::REMOTE_ADDR;


    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->connection_type = $settings[Settings::CONNECTION_TYPE];
    }


    /**
     * @return string Connection type as set by `BC_SECURITY_CONNECTION_TYPE` constant or empty string if constant is not set.
     */
    public static function getConnectionType(): string
    {
        return \defined('BC_SECURITY_CONNECTION_TYPE') ? BC_SECURITY_CONNECTION_TYPE : '';
    }


    /**
     * Get remote IP address according to connection type configured either by constant or backend setting.
     *
     * @return string
     */
    public function getRemoteAddress(): string
    {
        return IpAddress::get(self::getConnectionType() ?: $this->connection_type);
    }


    /**
     * Get server IP address. In the moment, there is no way to "configure" it.
     *
     * @return string
     */
    public function getServerAddress(): string
    {
        return IpAddress::getServer();
    }
}
