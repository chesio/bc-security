<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Helpers;

/**
 * @link https://digwp.com/2016/05/wordpress-admin-notices/
 */
abstract class AdminNotices
{
    const ERROR = 'notice-error';
    const WARNING = 'notice-warning';
    const SUCCESS = 'notice-success';
    const INFO = 'notice-info';

    /**
     * Add dismissible admin notice with given $message of given $type.
     *
     * @link https://make.wordpress.org/core/2015/04/23/spinners-and-dismissible-admin-notices-in-4-2/
     *
     * @param array|string $message Single message or array of messages.
     * @param string $type Type: error, warning, success or info.
     * @param bool $escape_html
     */
    public static function add($message, string $type = self::INFO, bool $escape_html = true)
    {
        add_action('admin_notices', function () use ($message, $type, $escape_html) {
            echo sprintf('<div class="notice %s is-dismissible">', $type);
            $messages = is_array($message) ? $message : [$message];
            array_walk($messages, function ($msg) use ($escape_html) {
                echo '<p>' . ($escape_html ? esc_html($msg) : $msg) . '</p>';
            });
            echo '</div>';
        });
    }
}
