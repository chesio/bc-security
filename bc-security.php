<?php
/**
 * Plugin Name: BC Security
 * Plugin URI: https://github.com/chesio/bc-security
 * Description: Helps keeping WordPress websites secure. Plugin requires PHP 7.0 or newer to run.
 * Version: 0.8.1
 * Author: Česlav Przywara <ceslav@przywara.cz>
 * Author URI: https://www.chesio.com
 * Requires PHP: 7.0
 * Requires WP: 4.7
 * Tested up to: 4.9
 * Text Domain: bc-security
 * GitHub Plugin URI: https://github.com/chesio/bc-security
 */

if (version_compare(PHP_VERSION, '7.0', '<')) {
    // Warn user that his/her PHP version is too low for this plugin to function.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>';
        echo esc_html(
            sprintf(
                __('BC Security plugin requires PHP 7.0 to function properly, but you have version %s installed. The plugin has been auto-deactivated.', 'bc-security'),
                PHP_VERSION
            )
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

// Construct plugin instance.
$bc_security = new \BlueChip\Security\Plugin(__FILE__, $GLOBALS['wpdb']);

// Register activation hook.
register_activation_hook(__FILE__, [$bc_security, 'activate']);
// Register deactivation hook.
register_deactivation_hook(__FILE__, [$bc_security, 'deactivate']);
// Ideally, uninstall hook would be registered here, but WordPress allows only static method in uninstall hook...

// Load the plugin.
$bc_security->load();
