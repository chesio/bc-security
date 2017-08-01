<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Setup;

class Core
{
    /**
     * @var string Connection type (see \BlueChip\Security\Setup\IpAddress)
     */
    private $connection_type = IpAddress::REMOTE_ADDR;


    /**
     * @param \BlueChip\Security\Setup\Settings $settings
     */
    public function __construct($settings)
    {
        $this->connection_type = $settings[Settings::CONNECTION_TYPE];
    }


    /**
     * Get remote IP address according to configured connection type.
     * @return string
     */
    public function getRemoteAddress()
    {
        return IpAddress::get($this->connection_type);
    }


    /**
     * Get server IP address. In the moment, there is no way to "configure" it.
     *
     * @return string
     */
    public function getServerAddress()
    {
        return IpAddress::getServer();
    }
}
