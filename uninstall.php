<?php
/**
 * Perform uninstall tasks.
 *
 * @package BC_Security
 */

// If file is not invoked by WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define path to plugin directory for autoloader
define('BC_SECURITY_PLUGIN_DIR', __DIR__);

// Get autoloader
require_once __DIR__ . '/includes/autoload.php';

// Uninstall the plugin
$bc_security = new \BlueChip\Security\Plugin($GLOBALS['wpdb']);
$bc_security->uninstall();
