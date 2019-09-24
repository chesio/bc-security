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
            is_multisite() ? user_can($user, 'manage_network') : user_can($user, 'manage_options'),
            $user
        );
    }


    /**
     * @return bool True, if current webserver interface is CLI, false otherwise.
     */
    public static function cli(): bool
    {
        return php_sapi_name() === 'cli';
    }


    /**
     * Return true, if current request is of given $type.
     *
     * @param string $type One of: admin, ajax, cron, frontend or wp-cli.
     * @return bool True, if current request is of given $type, false otherwise.
     */
    public static function request(string $type): bool
    {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return wp_doing_ajax();
            case 'cron':
                return wp_doing_cron();
            case 'frontend':
                return (!is_admin() || wp_doing_ajax()) && !wp_doing_cron();
            case 'wp-cli':
                return defined('WP_CLI') && WP_CLI;
            default:
                _doing_it_wrong(__METHOD__, sprintf('Unknown request type: %s', $type), '0.1.0');
                return false;
        }
    }
}
