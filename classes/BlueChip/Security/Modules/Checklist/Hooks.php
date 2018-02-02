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
     * Action: triggers when automatic checklist check encounters a problem.
     */
    const CHECK_ALERT = 'bc-security/action:checklist-check-alert';

    /**
     * Filter: allows to add/remove usernames to the list of obvious usernames.
     *
     * add_filter(\BlueChip\Security\Modules\Checklist\Hooks::OBVIOUS_USERNAMES, function ($usernames) {
     *     return array_merge(['mr-obvious'], $usernames);
     * }, 10, 1);
     */
    const OBVIOUS_USERNAMES = 'bc-security/filter:obvious-usernames';
}
