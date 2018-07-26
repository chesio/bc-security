<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Helpers;

/**
 * Useful helper functions
 */
abstract class RequestType
{
    /**
     * What type of request is this?
     *
     * @param string $type One of: admin, ajax, cron, frontend or wp-cli.
     * @return bool True, if current request is of given $type, false otherwise.
     */
    public static function is(string $type): bool
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
