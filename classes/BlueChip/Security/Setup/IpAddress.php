<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Setup;

/**
 * IP address retrieval (both remote and server)
 */
abstract class IpAddress
{
    // Direct connection
    const REMOTE_ADDR = 'REMOTE_ADDR';

    // Reverse proxy (or load balancer)
    const HTTP_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';

    // CloudFlare proxy
    const HTTP_CF_CONNECTING_IP = 'HTTP_CF_CONNECTING_IP';


    /**
     * Get a list of all connection types supported by the plugin.
     *
     * @param bool $explain Return array with type as key and explanation as value.
     * @return array Array of known (valid) connection types.
     */
    public static function enlist($explain = false)
    {
        $list = [
            self::REMOTE_ADDR => __('Direct connection to the Internet', 'bc-security'),
            self::HTTP_X_FORWARDED_FOR => __('Behind a reverse proxy or load balancer', 'bc-security'),
            self::HTTP_CF_CONNECTING_IP => __('Behind a CloudFlare proxy', 'bc-security'),
        ];

        return $explain ? $list : array_keys($list);
    }


    /**
     * Get remote address according to provided $type (with fallback to
     * REMOTE_ADDR).
     *
     * @param string $type
     * @return string Remote IP or empty string, if remote IP could not been
     * determined.
     */
    public static function get($type)
    {
        if (!in_array($type, self::enlist(), true)) {
            // Invalid type, fall back to direct address.
            $type = self::REMOTE_ADDR;
        }

        if (isset($_SERVER[$type])) {
            return self::getFirst($_SERVER[$type]);
        }

        // Not found, try to fall back to direct address, if proxy has been requested.
        if (($type !== self::REMOTE_ADDR) && isset($_SERVER[self::REMOTE_ADDR])) {

            // NOTE: Even though we fall back to direct address -- meaning you
            // can get a mostly working plugin when connection type is not set
            // properly -- it is not safe!
            //
            // Client can itself send HTTP_X_FORWARDED_FOR header fooling us
            // regarding which IP should be banned.
            return self::getFirst($_SERVER[self::REMOTE_ADDR]);
        }

        return '';
    }


    /**
     * Get raw $_SERVER value for connection $type.
     *
     * @param string $type
     * @return string
     */
    public static function getRaw($type)
    {
        return (in_array($type, self::enlist(), true) && isset($_SERVER[$type])) ? $_SERVER[$type] : '';
    }


    /**
     * Get IP address of webserver.
     *
     * @return string IP address of webserver or empty string if none provided (typically when running via PHP-CLI).
     */
    public static function getServer()
    {
        return isset($_SERVER['SERVER_ADDR']) ? self::getFirst($_SERVER['SERVER_ADDR']) : '';
    }


    /**
     * Get the first from possibly multiple $ip_addresses.
     * @param string $ip_addresses
     * @return string
     */
    private static function getFirst($ip_addresses)
    {
        // Note: explode always return an array with at least one item.
        $ips = array_map('trim', explode(',', $ip_addresses));
        return $ips[0];
    }
}
