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
     * Action: triggers when automatic checklist check encounters a problem.
     */
    const ADVANCED_CHECK_ALERT = 'bc-security/action:checklist-advanced-check-alert';

    /**
     * Filter: allows to add/remove usernames to the list of obvious usernames.
     *
     * add_filter(\BlueChip\Security\Modules\Checklist\Hooks::OBVIOUS_USERNAMES, function ($usernames) {
     *     return array_merge(['mr-obvious'], $usernames);
     * }, 10, 1);
     */
    const OBVIOUS_USERNAMES = 'bc-security/filter:obvious-usernames';

    /**
     * Filter: allows to filter list of plugins that are checked for removal from Plugins Directory at WordPress.org.
     */
    const PLUGINS_TO_CHECK_AT_WORDPRESS_ORG = 'bc-security/filter:plugins-to-check-at-wordpress.org';
}
