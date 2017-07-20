<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\IpBlacklist;

/**
 * Hooks available in IP Blacklist module
 */
interface Hooks
{
    /**
     * Filter: allows to change default lock duration for manually blacklisted
     * IP addresses.
     *
     * add_filter(\BlueChip\Security\Modules\IpBlacklist\Hooks::DEFAULT_MANUAL_LOCK_DURATION, function() {
     *     // Block for one year per default
     *     return YEAR_IN_SECONDS;
     * }, 10, 0);
     */
    const DEFAULT_MANUAL_LOCK_DURATION = 'bc_security_ip_blacklist_default_manual_lock_duration';
}
