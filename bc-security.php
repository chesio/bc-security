<?php
/**
 * Plugin Name: BC Security
 * Plugin URI: https://github.com/chesio/bc-security
 * Description: Helps keeping WordPress websites secure. Plugin requires PHP 5.6 or newer to run.
 * Version: 0.1.0-dev
 * Author: Česlav Przywara <ceslav@przywara.cz>
 * Author URI: https://www.chesio.com
 * Requires at least: 4.7
 * Tested up to: 4.8
 * Text Domain: bc-security
 * Domain Path: /languages
 */


/**
 * Warn user that his/her PHP version is too low for this plugin to function.
 */
function bc_security_upgrade_your_php_dude() {
    echo '<div class="error"><p>' . esc_html__('BC Security requires PHP 5.6 to function properly. Please upgrade your PHP version. The plugin has been auto-deactivated.', 'bc-security') . '</p></div>';
    // https://make.wordpress.org/plugins/2015/06/05/policy-on-php-versions/
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

/**
 * Self-deactivate the plugin
 */
function bc_security_self_deactivate() {
    deactivate_plugins(plugin_basename(__FILE__));
}


// PHP version check
if (version_compare(PHP_VERSION, '5.6', '<')) {
    add_action('admin_notices', 'bc_security_upgrade_your_php_dude');
    add_action('admin_init', 'bc_security_self_deactivate');
    return;
}


// Throw in some constants
define('BC_SECURITY_PLUGIN_DIR', __DIR__);
define('BC_SECURITY_PLUGIN_FILE', __FILE__);

// Register autoloader
require_once __DIR__ . '/includes/autoload.php';
// Bootstrap the plugin
require_once __DIR__ . '/includes/bootstrap.php';
