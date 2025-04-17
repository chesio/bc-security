<?php

declare(strict_types=1);

namespace BlueChip\Security\Helpers;

/**
 * @link https://digwp.com/2016/05/wordpress-admin-notices/
 */
abstract class AdminNotices
{
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const SUCCESS = 'success';
    public const INFO = 'info';

    /**
     * Add dismissible admin notice with given $message of given $type.
     *
     * @link https://make.wordpress.org/core/2023/10/16/introducing-admin-notice-functions-in-wordpress-6-4/
     * @link https://make.wordpress.org/core/2015/04/23/spinners-and-dismissible-admin-notices-in-4-2/
     *
     * @param string $message Message to display in admin notice.
     * @param string $type [optional] Type: 'notice-error', 'notice-warning', 'notice-success' or 'notice-info] (default).
     * @param bool $is_dismissible [optional] Should the notice be dismissible? Default is true.
     */
    public static function add(string $message, string $type = self::INFO, bool $is_dismissible = true): void
    {
        add_action('admin_notices', function () use ($message, $type, $is_dismissible) {
            wp_admin_notice($message, ['type' => $type, 'dismissible' => $is_dismissible,]);
        });
    }
}
