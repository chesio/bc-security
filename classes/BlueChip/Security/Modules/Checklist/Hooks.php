<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

/**
 * Hooks available in checklist module
 */
interface Hooks
{
    /**
     * Filter: allows to add/remove usernames to list of obvious usernames..
     *
     * add_filter(\BlueChip\Security\Modules\Checklist\Hooks::OBVIOUS_USERNAMES, function($usernames) {
     *     return array_merge(['mr-obvious'], $usernames);
     * }, 10, 1);
     */
    const OBVIOUS_USERNAMES = 'bc_security_status_obvious_usernames';
}
