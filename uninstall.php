<?php
/**
 * Perform uninstall tasks.
 *
 * @package BC_Security
 *
 * @link https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
 */

// If file is not invoked by WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define path to plugin directory for autoloader
define('BC_SECURITY_PLUGIN_DIR', __DIR__);

// Register autoloader
require_once __DIR__ . '/includes/autoload.php';

// Delete plugin settings from database
delete_option('bc-security-hardening');
delete_option('bc-security-login');
delete_option('bc-security-setup');

// Drop plugin tables
global $wpdb;
$wpdb->query(sprintf('DROP TABLE IF EXISTS %s', $wpdb->prefix . \BlueChip\Security\IpBlacklist\Manager::BLACKLIST_TABLE));
$wpdb->query(sprintf('DROP TABLE IF EXISTS %s', $wpdb->prefix . \BlueChip\Security\Login\Bookkeeper::FAILED_LOGINS_TABLE));
