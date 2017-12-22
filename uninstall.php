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

// Register autoloader for this plugin.
require_once __DIR__ . '/autoload.php';

// Construct plugin instance.
$bc_security = new \BlueChip\Security\Plugin(
    plugin_basename(preg_replace('/uninstall.php$/', 'bc-security.php', __FILE__)), // A bit hacky, but functional approach.
    $GLOBALS['wpdb']
);
// Run uninstall actions.
$bc_security->uninstall();
