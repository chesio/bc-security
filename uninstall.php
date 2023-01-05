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

// Construct plugin instance and run uninstall actions.
(new \BlueChip\Security\Plugin(__DIR__ . '/bc-security.php', $GLOBALS['wpdb']))->uninstall();
