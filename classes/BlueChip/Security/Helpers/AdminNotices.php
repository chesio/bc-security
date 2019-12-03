<?php

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
     * @param string $type [optional] Type: 'notice-error', 'notice-warning', 'notice-success' or 'notice-info] (default).
     * @param bool $is_dismissible [optional] Should the notice be dismissible? Default is true.
     * @param bool $escape_html [optional] Should the content of message be HTML escaped? Default is true.
     */
    public static function add($message, string $type = self::INFO, bool $is_dismissible = true, bool $escape_html = true)
    {
        $classes = \implode(' ', \array_filter(['notice', $type, $is_dismissible ? 'is-dismissible' : '']));
        add_action('admin_notices', function () use ($message, $classes, $escape_html) {
            echo '<div class="' . $classes . '">';
            $messages = \is_array($message) ? $message : [$message];
            \array_walk($messages, function ($msg) use ($escape_html) {
                echo '<p>' . ($escape_html ? esc_html($msg) : $msg) . '</p>';
            });
            echo '</div>';
        });
    }
}
