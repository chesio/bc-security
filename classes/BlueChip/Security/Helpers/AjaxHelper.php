<?php

namespace BlueChip\Security\Helpers;

/**
 * @link https://codex.wordpress.org/AJAX
 */
abstract class AjaxHelper
{
    /**
     * @var string
     */
    private const WP_AJAX_PREFIX = 'wp_ajax_';


    /**
     * Register callback as handler for AJAX action. Handler will be only executed, if nonce check passes.
     *
     * @param string $action
     * @param callable $handler
     */
    public static function addHandler(string $action, callable $handler)
    {
        add_action(self::WP_AJAX_PREFIX . $action, function () use ($action, $handler) {
            // Check AJAX referer for given action - will die, if invalid.
            check_ajax_referer($action);

            \call_user_func($handler);
        }, 10, 0);
    }


    /**
     * Inject AJAX setup to page. Should be called *after* a script with $handle is registered or enqueued!
     *
     * @param string $handle
     * @param string $object_name
     * @param string $action
     * @param array $data
     */
    public static function injectSetup(string $handle, string $object_name, string $action, array $data = [])
    {
        add_action('admin_enqueue_scripts', function () use ($handle, $object_name, $action, $data) {
            // Default localization data for every AJAX request.
            $l10n = [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce($action),
                'action' => $action,
            ];

            wp_localize_script(
                $handle,
                $object_name,
                \array_merge($data, $l10n)
            );
        }, 10, 0);
    }
}
