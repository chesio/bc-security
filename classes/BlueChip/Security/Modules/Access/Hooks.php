<?php

namespace BlueChip\Security\Modules\Access;

/**
 * Hooks available in access module
 */
interface Hooks
{
    /**
     * Action: triggers when request results in external blocklist hit.
     */
    public const EXTERNAL_BLOCKLIST_HIT_EVENT = 'bc-security/action:external-blocklist-hit';

    /**
     * Action: triggers when request results in internal blocklist hit.
     */
    public const INTERNAL_BLOCKLIST_HIT_EVENT = 'bc-security/action:internal-blocklist-hit';

    /**
     * Filter: allows to filter result of "is IP address blocked" check.
     *
     * add_filter(
     *     \BlueChip\Security\Modules\Access\Hooks::IS_IP_ADDRESS_BLOCKED,
     *     function (bool $result, string $ip_address, int $scope) {
     *         // Block any IP address that starts with "1"
     *         return str_starts_with($ip_address, '1');
     *     },
     *     10,
     *     3
     * );
     */
    public const IS_IP_ADDRESS_BLOCKED = 'bc-security/filter:is-ip-address-blocked';
}
