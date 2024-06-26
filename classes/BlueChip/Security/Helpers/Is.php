<?php

declare(strict_types=1);

namespace BlueChip\Security\Helpers;

use WP_User;

/**
 * Various is::xxx() helpers.
 */
abstract class Is
{
    /**
     * Return true if current user is an admin.
     */
    public static function admin(WP_User $user): bool
    {
        return apply_filters(
            Hooks::IS_ADMIN,
            is_multisite() ? user_can($user, 'manage_network') : user_can($user, 'manage_options'),
            $user
        );
    }


    /**
     * @return bool True if current webserver interface is CLI, false otherwise.
     */
    public static function cli(): bool
    {
        return \PHP_SAPI === 'cli';
    }


    /**
     * @return bool True if the website is running in live environment, false otherwise.
     */
    public static function live(): bool
    {
        // Consider both production and staging environment as live.
        return apply_filters(
            Hooks::IS_LIVE,
            \in_array(wp_get_environment_type(), ['production', 'staging'], true)
        );
    }


    /**
     * Return true if current request is of given $type.
     *
     * @param string $type One of: admin, ajax, cron, frontend or wp-cli.
     *
     * @return bool True if current request is of given $type, false otherwise.
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
                return \defined('WP_CLI') && \constant('WP_CLI');
            default:
                _doing_it_wrong(__METHOD__, \sprintf('Unknown request type: %s', $type), '0.1.0');
                return false;
        }
    }
}
