<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security;

/**
 * Main plugin class
 */
class Plugin
{
    /**
     * @var \BlueChip\Security\Admin
     */
    public $admin;

    /**
     * @var \BlueChip\Security\Core\CronJob[] Plugin cron jobs
     */
    private $cron_jobs;

    /**
     * @var array Plugin module objects
     */
    private $modules;

    /**
     * @var \BlueChip\Security\Core\Settings[] Plugin setting objects
     */
    private $settings;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * Construct the plugin instance.
     *
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;

        // Read plugin settings.
        $this->settings = $this->constructSettings();

        // Get setup info.
        $setup = new Setup\Core($this->settings['setup']);

        // IP addresses are at core interest within this plugin :)
        $remote_address = $setup->getRemoteAddress();
        $server_address = $setup->getServerAddress();

        // Init admin, if necessary.
        $this->admin = is_admin() ? new Admin() : null;

        // Construct modules.
        $this->modules = $this->constructModules($wpdb, $remote_address, $server_address, $this->settings);

        // Construct cron jobs.
        $this->cron_jobs = $this->constructCronJobs($this->settings, $this->modules);
    }


    /**
     * Construct plugin settings.
     *
     * @return array
     */
    private function constructSettings()
    {
        return [
            'hardening'     => new Modules\Hardening\Settings('bc-security-hardening'),
            'log'           => new Modules\Log\Settings('bc-security-log'),
            'login'         => new Modules\Login\Settings('bc-security-login'),
            'notifications' => new Modules\Notifications\Settings('bc-security-notifications'),
            'setup'         => new Setup\Settings('bc-security-setup'),
        ];
    }


    /**
     * Construct plugin modules.
     *
     * @param \wpdb $wpdb
     * @param string $remote_address
     * @param string $server_address
     * @param array $settings
     * @return array
     */
    private function constructModules(\wpdb $wpdb, $remote_address, $server_address, array $settings)
    {
        $logger     = new Modules\Log\Logger($wpdb, $remote_address);
        $verifier   = new Modules\Checksums\Verifier();
        $monitor    = new Modules\Events\Monitor($remote_address, $server_address);
        $notifier   = new Modules\Notifications\Watchman($settings['notifications'], $remote_address, $logger);
        $hardening  = new Modules\Hardening\Core($settings['hardening']);
        $bl_manager = new Modules\IpBlacklist\Manager($wpdb);
        $bl_bouncer = new Modules\IpBlacklist\Bouncer($remote_address, $bl_manager);
        $bookkeeper = new Modules\Login\Bookkeeper($settings['login'], $wpdb);
        $gatekeeper = new Modules\Login\Gatekeeper($settings['login'], $remote_address, $bookkeeper, $bl_manager);

        return [
            'logger'            => $logger,
            'checksum-verifier' => $verifier,
            'events-monitor'    => $monitor,
            'notifier'          => $notifier,
            'hardening-core'    => $hardening,
            'blacklist-manager' => $bl_manager,
            'blacklist-bouncer' => $bl_bouncer,
            'login-bookkeeper'  => $bookkeeper,
            'login-gatekeeper'  => $gatekeeper,
        ];
    }


    /**
     * Construct plugin cron jobs.
     *
     * @param array $settings
     * @param array $modules
     * @return array
     */
    private function constructCronJobs(array $settings, array $modules)
    {
        return [
            'blacklist-cleaner' => new Core\CronJob(
                '01:02:03',
                Core\CronJob::RECUR_DAILY,
                'bc-security/ip-blacklist-clean-up',
                [$modules['blacklist-manager'], 'prune']
            ),
            'log-cleaner-by-age' => new Core\CronJob(
                '02:03:04',
                Core\CronJob::RECUR_DAILY,
                'bc-security/logs-clean-up-by-age',
                [$modules['logger'], 'pruneByAge'],
                [$settings['log']->getMaxAge()]
            ),
            'log-cleaner-by-size' => new Core\CronJob(
                '03:04:05',
                Core\CronJob::RECUR_DAILY,
                'bc-security/logs-clean-up-by-size',
                [$modules['logger'], 'pruneBySize'],
                [$settings['log']->getMaxSize()]
            ),
            'checksum-verifier' => new Core\CronJob(
                '04:05:06',
                Core\CronJob::RECUR_DAILY,
                'bc-security/checksum-verifier',
                [$modules['checksum-verifier'], 'runCheck']
            ),
        ];
    }


    /**
     * Load the plugin by hooking into WordPress actions and filters.
     * Method should be invoked immediately on plugin load.
     */
    public function load()
    {
        // Load all modules that require immediate loading.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Loadable) {
                $module->load();
            }
        }

        // Register initialization method.
        add_action('init', [$this, 'init'], 10, 0);
    }


    /**
     * Perform initialization tasks.
     * Method should be run (early) in init hook.
     */
    public function init()
    {
        // Initialize all modules that require initialization.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Initializable) {
                $module->init();
            }
        }

        if ($this->admin) {
            // Initialize admin interface.
            $this->admin->init()
                // Setup comes first...
                ->addPage(new Setup\AdminPage($this->settings['setup']))
                // ...then comes modules pages.
                ->addPage(new Modules\Checklist\AdminPage($this->wpdb))
                ->addPage(new Modules\Hardening\AdminPage($this->settings['hardening']))
                ->addPage(new Modules\Login\AdminPage($this->settings['login']))
                ->addPage(new Modules\IpBlacklist\AdminPage($this->modules['blacklist-manager'], $this->cron_jobs['blacklist-cleaner']))
                ->addPage(new Modules\Notifications\AdminPage($this->settings['notifications']))
                ->addPage(new Modules\Log\AdminPage($this->settings['log'], $this->modules['logger']))
            ;
        }
    }


    /**
     * Perform activation and installation tasks.
     * Method should be run on plugin activation.
     *
     * @link https://developer.wordpress.org/plugins/the-basics/activation-deactivation-hooks/
     */
    public function activate()
    {
        // Install every module that requires it.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Installable) {
                $module->install();
            }
        }

        // Activate every module that requires it.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Activable) {
                $module->activate();
            }
        }

        // Activate cron jobs.
        foreach ($this->cron_jobs as $cron_job) {
            $cron_job->activate();
        }
    }


    /**
     * Perform deactivation tasks.
     * Method should be run on plugin deactivation.
     *
     * @link https://developer.wordpress.org/plugins/the-basics/activation-deactivation-hooks/
     */
    public function deactivate()
    {
        // Deactivate cron jobs.
        foreach ($this->cron_jobs as $cron_job) {
            $cron_job->deactivate();
        }

        // Deactivate every module that requires it.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Activable) {
                $module->deactivate();
            }
        }
    }


    /**
     * Perform uninstallation tasks.
     * Method should be run on plugin uninstall.
     *
     * @link https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
     */
    public function uninstall()
    {
        // Remove plugin settings.
        foreach ($this->settings as $settings) {
            delete_option($settings->getOptionName());
        }

        // Uninstall every module that requires it.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Installable) {
                $module->uninstall();
            }
        }
    }
}
