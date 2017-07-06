<?php
/**
 * Bootstrap plugin and store main instance in global variable $bc_security.
 *
 * This file requires PHP 5.3 or newer to run!
 *
 * @package BC_Security
 */

defined('ABSPATH') or die('Das past schon nicht!');

/** @var \BlueChip\Security\Plugin BC Security instance */
$bc_security = new \BlueChip\Security\Plugin($GLOBALS['wpdb']);

// Ok, go!
$bc_security->init();
