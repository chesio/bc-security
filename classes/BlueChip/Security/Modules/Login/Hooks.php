<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Login;

/**
 * Hooks available in login security module
 */
interface Hooks
{
    /**
     * Action: triggers when lockout event happens.
     */
    const LOCKOUT_EVENT = 'bc_security_login_lockout_event';

    /**
     * Filter: allows to add/remove usernames from blacklist (filters whatever
     * is stored in plugin settings).
     *
     * add_filter(\BlueChip\Security\Modules\Login\Hooks::USERNAME_BLACKLIST, function($usernames) {
     *     // Make sure "admin" and "administrator" are always blocked:
     *     return array_merge($usernames, ['admin', 'administrator']);
     * }, 10, 1);
     */
    const USERNAME_BLACKLIST = 'bc_security_login_username_blacklist';
}
