<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Integration;

class Bootstrap
{
    /** @var string plugin directory */
    public $plugin_dir;

    /** @var string directory where wordpress-tests-lib is installed */
    public $wp_tests_dir;


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
        $this->plugin_dir   = dirname(dirname(dirname(__DIR__)));
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

        // Load test function so tests_add_filter() is available.
        require_once $this->wp_tests_dir . '/includes/functions.php';

        tests_add_filter('muplugins_loaded', function () {
            // Clean existing install first.
            define('WP_UNINSTALL_PLUGIN', true);
            require_once $this->plugin_dir . '/uninstall.php';
        });

        tests_add_filter('muplugins_loaded', function () {
            // Load the plugin.
            require_once $this->plugin_dir . '/bc-security.php';
            // Manually activate the plugin.
            $bc_security->activate();
        });

        // Start up the WP testing environment.
        require_once $this->wp_tests_dir . '/includes/bootstrap.php';
    }
}
