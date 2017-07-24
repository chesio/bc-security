<?php
/**
 * Perform plugin uninstall.
 *
 * @package BC_Security
 */

// If file is not invoked by WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Throw in some constants.
define('BC_SECURITY_PLUGIN_DIR', __DIR__);

// Get autoloader.
require_once __DIR__ . '/includes/autoload.php';

// Construct plugin instance.
$bc_security = new \BlueChip\Security\Plugin($GLOBALS['wpdb']);
// Run uninstall actions.
$bc_security->uninstall();
