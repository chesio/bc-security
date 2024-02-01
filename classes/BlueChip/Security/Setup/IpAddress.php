<?php

declare(strict_types=1);

namespace BlueChip\Security\Setup;

/**
 * IP address retrieval (both remote and server)
 *
 * @link https://distinctplace.com/2014/04/23/story-behind-x-forwarded-for-and-x-real-ip-headers/
 */
abstract class IpAddress
{
    // Direct connection
    public const REMOTE_ADDR = 'REMOTE_ADDR';

    // Reverse proxy (or load balancer) - may contain multiple IP addresses.
    public const HTTP_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';

    // Presumably real IP of the client - set by some proxies.
    public const HTTP_X_REAL_IP = 'HTTP_X_REAL_IP';

    // CloudFlare CDN (~ reverse proxy)
    public const HTTP_CF_CONNECTING_IP = 'HTTP_CF_CONNECTING_IP';


    /**
     * Get a list of all connection types supported by the plugin.
     *
     * @return array<string,string> Array of known (valid) connection types.
     */
    public static function enlist(): array
    {
        return [
            self::REMOTE_ADDR => __('Direct connection to the Internet', 'bc-security'),
            self::HTTP_CF_CONNECTING_IP => __('Behind CloudFlare CDN and reverse proxy', 'bc-security'),
            self::HTTP_X_FORWARDED_FOR => __('Behind a reverse proxy or load balancer', 'bc-security'),
            self::HTTP_X_REAL_IP => __('Behind a reverse proxy or load balancer', 'bc-security'),
        ];
    }


    /**
     * Get remote address according to provided $type (with fallback to REMOTE_ADDR).
     *
     * @param string $type
     *
     * @return string Remote IP or empty string if remote IP could not been determined.
     */
    public static function get(string $type): string
    {
        if (!\array_key_exists($type, self::enlist())) {
            // Invalid type, fall back to direct address.
            $type = self::REMOTE_ADDR;
        }

        if (isset($_SERVER[$type])) {
            return self::parseFrom($_SERVER[$type]);
        }

        // Not found: try to fall back to direct address if proxy has been requested.
        if (($type !== self::REMOTE_ADDR) && isset($_SERVER[self::REMOTE_ADDR])) {
            // NOTE: Even though we fall back to direct address -- meaning you
            // can get a mostly working plugin when connection type is not set
            // properly -- it is not safe!
            //
            // Client can itself send HTTP_X_FORWARDED_FOR header fooling us
            // regarding which IP should be banned.
            return self::parseFrom($_SERVER[self::REMOTE_ADDR]);
        }

        return '';
    }


    /**
     * Get raw $_SERVER value for connection $type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getRaw(string $type): string
    {
        return \array_key_exists($type, self::enlist()) ? ($_SERVER[$type] ?? '') : '';
    }


    /**
     * Get IP address of webserver.
     *
     * @return string IP address of webserver or empty string if none provided (typically when running via PHP-CLI).
     */
    public static function getServer(): string
    {
        return array_key_exists('SERVER_ADDR', $_SERVER) ? self::parseFrom($_SERVER['SERVER_ADDR']) : '';
    }


    /**
     * Attempt to get a valid IP address from potentially insecure (user-provided) data.
     */
    private static function parseFrom(string $maybe_list_of_ip_addresses): string
    {
        return self::validate(self::getFirst($maybe_list_of_ip_addresses)) ?? '';
    }


    /**
     * Get the first from possibly multiple $ip_addresses.
     */
    private static function getFirst(string $ip_addresses): string
    {
        // Note: explode always return an array with at least one item.
        $ips = \array_map('trim', \explode(',', $ip_addresses));
        return $ips[0];
    }


    /**
     * Validate given $ip_address - return null if invalid.
     */
    private static function validate(string $ip_address): ?string
    {
        return \filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE);
    }
}
