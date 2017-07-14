<?php
/**
 * Register autoloader for classes shipped with the plugin.
 *
 * @package BC_Security
 */

defined('BC_SECURITY_PLUGIN_DIR') or die('Das past schon nicht!');

// Register autoload function
spl_autoload_register(function ($class) {
    // Only autoload classes shipped with the plugin.
    if ((strpos($class, 'BlueChip\\Security') !== 0) && (strpos($class, 'Psr\\Log') !== 0)) {
        return;
    }

    // Get absolute name of class file
    $file = BC_SECURITY_PLUGIN_DIR . '/classes/' . str_replace('\\', '/', $class) . '.php';

    // If the class file is readable, load it!
    if (is_readable($file)) {
        require_once $file;
    }
});
