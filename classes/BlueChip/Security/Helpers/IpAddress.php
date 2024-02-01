<?php

declare(strict_types=1);

namespace BlueChip\Security\Helpers;

abstract class IpAddress
{
    /**
     * @param string $ip_address IPv4 address to check
     * @param string $cidr_range
     *
     * @link https://stackoverflow.com/a/594134
     */
    public static function matchesPrefix(string $ip_address, string $cidr_range): bool
    {
        [$subnet, $bits] = \explode('/', $cidr_range);

        // Convert all to integers.
        $bits = (int) $bits;
        $ip_address = \ip2long($ip_address);
        $subnet = \ip2long($subnet);

        // Calculate mask.
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; // In case the supplied subnet wasn't correctly aligned.

        return ($ip_address & $mask) === $subnet;
    }


    public static function sanitizePrefix(string $cidr_range): string
    {
        return \str_contains($cidr_range, '/') ? $cidr_range : ($cidr_range .= '/32');
    }
}
