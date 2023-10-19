<?php

define('WPINC', '');
define('WP_CONTENT_DIR', '');
define('WP_CONTENT_URL', '');
define('WP_PLUGIN_URL', '');
define('AUTH_COOKIE', '');
define('SECURE_AUTH_COOKIE', '');
define('LOGGED_IN_COOKIE', '');

// Upcoming in WordPress 6.4:
function wp_admin_notice (string $message, array $args): void {}
