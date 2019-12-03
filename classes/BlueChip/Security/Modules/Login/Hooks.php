<?php

namespace BlueChip\Security\Modules\Login;

/**
 * Hooks available in login security module
 */
interface Hooks
{
    /**
     * Action: triggers when lockout event happens.
     */
    const LOCKOUT_EVENT = 'bc-security/action:login-lockout-event';

    /**
     * Filter: allows to add/remove usernames from blacklist (filters whatever
     * is stored in plugin settings).
     *
     * add_filter(\BlueChip\Security\Modules\Login\Hooks::USERNAME_BLACKLIST, function (array $usernames): array {
     *     // Make sure "admin" and "administrator" are always blocked:
     *     return array_merge($usernames, ['admin', 'administrator']);
     * }, 10, 1);
     */
    const USERNAME_BLACKLIST = 'bc-security/filter:username-blacklist';
}
