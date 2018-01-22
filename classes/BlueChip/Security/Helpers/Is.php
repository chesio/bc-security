<?php
/**
 * @package BC_Security
 */
namespace BlueChip\Security\Helpers;

/**
 * Various is::xxx() helpers.
 */
class Is
{
    /**
     * Return true, if current user is an admin.
     *
     * @param \WP_User $user
     * @return bool
     */
    public static function admin(\WP_User $user): bool
    {
        return apply_filters(
            Hooks::IS_ADMIN,
            is_multisite() ? user_can($user, 'manage_network') : user_can($user, 'manage_options')
        );
    }
}
