<?php
/**
 * PHPUnit bootstrap file
 *
 * @package BC Security
 */

class BcSecurityUnitTestsBootstrap {

    /** @var BcSecurityUnitTestsBootstrap instance */
    protected static $instance = null;

    /** @var string testing directory */
    public $tests_dir;

    /** @var string plugin directory */
    public $plugin_dir;

    /** @var string directory where wordpress-tests-lib is installed */
    public $wp_tests_dir;

    /**
     * Setup the unit testing environment.
     */
    public function __construct()
    {
        // Make sure strict standards are reported
        error_reporting(E_ALL);

        // Ensure server variable is set for WP email functions.
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'bc-security.test';
        }

        $this->tests_dir    = __DIR__;
        $this->plugin_dir   = dirname($this->tests_dir);
        $this->wp_tests_dir = getenv('WP_TESTS_DIR') ?: rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';

        // Require Composer autoloader
        require_once $this->plugin_dir . '/vendor/autoload.php';

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

    /**
     * Get the single class instance.
     *
     * @return BcSecurityUnitTestsBootstrap
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

BcSecurityUnitTestsBootstrap::getInstance();
