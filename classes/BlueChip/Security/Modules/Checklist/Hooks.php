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
     * Action: triggers when a single advanced check does not pass during checklist monitoring run.
     */
    const ADVANCED_CHECK_ALERT = 'bc-security/action:checklist-advanced-check-alert';

    /**
     * Action: triggers when any of basic checks does not pass during checklist monitoring run.
     */
    const BASIC_CHECKS_ALERT = 'bc-security/action:checklist-basic-checks-alert';

    /**
     * Filter: allows to add/remove usernames to the list of obvious usernames.
     *
     * add_filter(\BlueChip\Security\Modules\Checklist\Hooks::OBVIOUS_USERNAMES, function ($usernames) {
     *     return array_merge(['mr-obvious'], $usernames);
     * }, 10, 1);
     */
    const OBVIOUS_USERNAMES = 'bc-security/filter:obvious-usernames';

    /**
     * Filter: filters list of files that should be ignored during check for modified core files.
     */
    const IGNORED_CORE_MODIFIED_FILES = 'bc-security/filter:modified-files-ignored-in-core-integrity-check';

    /**
     * Filter: filters list of files that should be ignored during check for unknown core files.
     */
    const IGNORED_CORE_UNKNOWN_FILES = 'bc-security/filter:unknown-files-ignored-in-core-integrity-check';

    /**
     * Filter: filters list of plugins to check in integrity check.
     */
    const PLUGINS_TO_CHECK_FOR_INTEGRITY = 'bc-security/filter:plugins-to-check-for-integrity';

    /**
     * Filter: allows to filter list of plugins that are checked for removal from Plugins Directory at WordPress.org.
     */
    const PLUGINS_TO_CHECK_FOR_REMOVAL = 'bc-security/filter:plugins-to-check-for-removal';
}
