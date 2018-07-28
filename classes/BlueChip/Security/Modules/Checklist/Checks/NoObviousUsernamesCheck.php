<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class NoObviousUsernamesCheck extends Checklist\BasicCheck
{
    public function __construct()
    {
        parent::__construct(
            __('No obvious usernames exist', 'bc-security'),
            sprintf(__('Usernames like "admin" and "administrator" are often used in brute force attacks and <a href="%s">should be avoided</a>.', 'bc-security'), 'https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity')
        );
    }


    public function run(): Checklist\CheckResult
    {
        // Get (filtered) list of obvious usernames to test.
        $obvious = apply_filters(Checklist\Hooks::OBVIOUS_USERNAMES, ['admin', 'administrator']);
        // Check for existing usernames.
        $existing = array_filter($obvious, function ($username) {
            return get_user_by('login', $username);
        });

        return empty($existing)
            ? new Checklist\CheckResult(true, esc_html__('None of the following usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $obvious) . '</em>')
            : new Checklist\CheckResult(false, esc_html__('The following obvious usernames exists on the system:', 'bc-security') . ' <em>' . implode(', ', $existing) . '</em>')
        ;
    }
}
