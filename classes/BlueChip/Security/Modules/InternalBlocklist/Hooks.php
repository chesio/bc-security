<?php

namespace BlueChip\Security\Modules\InternalBlocklist;

/**
 * Hooks available in internal blocklist module
 */
interface Hooks
{
    /**
     * Filter: allows to change default lock duration for manually blocklisted IP addresses.
     *
     * add_filter(\BlueChip\Security\Modules\InternalBlocklist\Hooks::DEFAULT_MANUAL_LOCK_DURATION, function () {
     *     // Block for one year per default
     *     return YEAR_IN_SECONDS;
     * }, 10, 0);
     */
    public const DEFAULT_MANUAL_LOCK_DURATION = 'bc-security/filter:internal-blocklist-default-manual-lock-duration';

    /**
     * Filter: allows to filter result of "is IP address locked" check that is whether an IP address in on internal blocklist.
     *
     * add_filter(
     *     \BlueChip\Security\Modules\InternalBlocklist\Hooks::IS_IP_ADDRESS_LOCKED,
     *     function (bool $result, string $ip_address, int $scope) {
     *         // Block any IP address that starts with "1"
     *         return str_starts_with($ip_address, '1');
     *     },
     *     10,
     *     3
     * );
     */
    public const IS_IP_ADDRESS_LOCKED = 'bc-security/filter:is-ip-address-locked';
}
