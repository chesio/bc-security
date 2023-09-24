<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration;

class Bootstrap
{
    /** @var string plugin directory */
    public $plugin_dir;

    /** @var string directory where wordpress-tests-lib is installed */
    public $wp_tests_dir;


    /**
     * @return string Path to root directory of the plugin.
     */
    public static function getPluginRootDirectory(): string
    {
        return dirname(dirname(dirname(__DIR__)));
    }


    /**
     * @return string Path to where WordPress tests library is installed.
     */
    public static function getWordPressTestsDirectory(): string
    {
        return getenv('WP_TESTS_DIR') ?: rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
    }


    /**
     * Construct the bootstraper.
     */
    public function __construct()
    {
        $this->plugin_dir   = self::getPluginRootDirectory();
        $this->wp_tests_dir = self::getWordPressTestsDirectory();
    }


    /**
     * Setup the integration testing environment.
     */
    public function run()
    {
        // Make sure strict standards are reported
        error_reporting(E_ALL);

        // Ensure server variable is set for WP email functions.
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'bc-security.test';
        }

        // Give access to tests_add_filter() function.
        require_once $this->wp_tests_dir . '/includes/functions.php';

        // Clean existing install first.
        tests_add_filter('muplugins_loaded', function () {
            define('WP_UNINSTALL_PLUGIN', true);
            require_once $this->plugin_dir . '/uninstall.php';
        });

        tests_add_filter('muplugins_loaded', function () {
            // Bootstrap the plugin ...
            $bc_security = require_once $this->plugin_dir . '/bc-security.php';
            // ... activate it ...
            do_action('activate_' . plugin_basename($this->plugin_dir . '/bc-security.php'));
            // ... but do not load it yet - it is loaded manually before every test.
            remove_action('plugins_loaded', [$bc_security, 'load'], 0);
        });

        // Start up the WP testing environment.
        require_once $this->wp_tests_dir . '/includes/bootstrap.php';
    }
}
