<?php

namespace BlueChip\Security\Modules\Hardening;

interface Hooks
{
    /**
     * Filter: allows to disable display of pwned password warning.
     *
     * // Only display the warning on main dashboard page.
     * add_filter(\BlueChip\Security\Modules\Hardening\Hooks::SHOW_PWNED_PASSWORD_WARNING, function (bool $show, \WP_Screen $screen, \WP_User $user) {
     *     return $screen->base === 'dashboard';
     * }, 10, 3);
     */
    const SHOW_PWNED_PASSWORD_WARNING = 'bc-security/filter:show-pwned-password-warning';
}
