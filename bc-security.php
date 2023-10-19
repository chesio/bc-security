<?php

/**
 * Plugin Name: BC Security
 * Plugin URI: https://github.com/chesio/bc-security
 * Description: Helps keeping WordPress websites secure.
 * Version: 0.22.0-dev
 * Author: Česlav Przywara <ceslav@przywara.cz>
 * Author URI: https://www.chesio.com
 * Requires PHP: 8.1
 * Requires at least: 6.2
 * Tested up to: 6.4
 * Text Domain: bc-security
 * GitHub Plugin URI: https://github.com/chesio/bc-security
 * Update URI: https://github.com/chesio/bc-security
 */

declare(strict_types=1);

if (version_compare(PHP_VERSION, '8.1', '<')) {
    // Warn user that his/her PHP version is too low for this plugin to function.
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo esc_html(
            sprintf(
                __('BC Security plugin requires PHP 8.1 to function properly, but you have version %s installed. The plugin has been auto-deactivated.', 'bc-security'),
                PHP_VERSION
            )
        );
        echo '</p></div>';
        // Warn user that his/her PHP version is no longer supported.
        echo '<div class="notice notice-warning"><p>';
        echo sprintf(
            __('PHP version %1$s is <a href="%2$s">no longer supported</a>. You should consider upgrading PHP on your webhost.', 'bc-security'),
            PHP_VERSION,
            'https://www.php.net/supported-versions.php'
        );
        echo '</p></div>';
        // https://make.wordpress.org/plugins/2015/06/05/policy-on-php-versions/
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }, 10, 0);

    // Self deactivate.
    add_action('admin_init', function () {
        deactivate_plugins(plugin_basename(__FILE__));
    }, 10, 0);

    // Bail.
    return;
}


// Register autoloader for this plugin.
require_once __DIR__ . '/autoload.php';

return call_user_func(function () {
    // Construct plugin instance.
    $bc_security = new \BlueChip\Security\Plugin(__FILE__, $GLOBALS['wpdb']);

    // Register activation hook.
    register_activation_hook(__FILE__, [$bc_security, 'activate']);
    // Register deactivation hook.
    register_deactivation_hook(__FILE__, [$bc_security, 'deactivate']);

    // Boot up the plugin immediately after all plugins are loaded.
    add_action('plugins_loaded', [$bc_security, 'load'], 0, 0);

    // Return the instance.
    return $bc_security;
});
