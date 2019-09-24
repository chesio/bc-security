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
            \sprintf(
                /* translators: 1: link to Codex page on WordPress hardening */
                esc_html__('Usernames like "admin" and "administrator" are often used in brute force attacks and %1$s.', 'bc-security'),
                '<a href="' . esc_url(__('https://codex.wordpress.org/Hardening_WordPress#Security_through_obscurity', 'bc-security')) . '" rel="noreferrer">' . esc_html__('should be avoided', 'bc-security') . '</a>'
            )
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        // Get (filtered) list of obvious usernames to test.
        $obvious = apply_filters(Checklist\Hooks::OBVIOUS_USERNAMES, ['admin', 'administrator']);
        // Check for existing usernames.
        $existing = \array_filter($obvious, function ($username) {
            return get_user_by('login', $username);
        });

        return empty($existing)
            ? new Checklist\CheckResult(true, esc_html__('None of the following usernames exists on the system:', 'bc-security') . ' <em>' . \implode(', ', $obvious) . '</em>')
            : new Checklist\CheckResult(false, esc_html__('The following obvious usernames exists on the system:', 'bc-security') . ' <em>' . \implode(', ', $existing) . '</em>')
        ;
    }
}
