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
     * @var string Plugin basename
     */
    private $basename;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * Construct the plugin instance.
     *
     * @param string $basename Plugin basename
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(string $basename, \wpdb $wpdb)
    {
        $this->basename = $basename;
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
    private function constructSettings(): array
    {
        return [
            'cron-jobs'     => new Modules\Cron\Settings('bc-security-cron-jobs'),
            'checklist'     => new Modules\Checklist\Settings('bc-security-checklist'),
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
    private function constructModules(\wpdb $wpdb, string $remote_address, string $server_address, array $settings): array
    {
        $logger             = new Modules\Log\Logger($wpdb, $remote_address);
        $checklist_manager  = new Modules\Checklist\Manager($settings['checklist'], $wpdb);
        $core_verifier      = new Modules\Checksums\CoreVerifier();
        $plugins_verifier   = new Modules\Checksums\PluginsVerifier();
        $monitor            = new Modules\Events\Monitor($remote_address, $server_address);
        $notifier           = new Modules\Notifications\Watchman($settings['notifications'], $remote_address, $logger);
        $hardening          = new Modules\Hardening\Core($settings['hardening']);
        $blacklist_manager  = new Modules\IpBlacklist\Manager($wpdb);
        $blacklist_bouncer  = new Modules\IpBlacklist\Bouncer($remote_address, $blacklist_manager);
        $bookkeeper         = new Modules\Login\Bookkeeper($settings['login'], $wpdb);
        $gatekeeper         = new Modules\Login\Gatekeeper($settings['login'], $remote_address, $bookkeeper, $blacklist_manager);

        return [
            'logger'            => $logger,
            'checklist-manager' => $checklist_manager,
            'core-verifier'     => $core_verifier,
            'plugins-verifier'  => $plugins_verifier,
            'events-monitor'    => $monitor,
            'notifier'          => $notifier,
            'hardening-core'    => $hardening,
            'blacklist-manager' => $blacklist_manager,
            'blacklist-bouncer' => $blacklist_bouncer,
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
    private function constructCronJobs(array $settings, array $modules): array
    {
        return [
            'checklist-checker' => new Modules\Cron\Job(
                $settings['cron-jobs'],
                Modules\Cron\Job::RUN_AT_NIGHT,
                Modules\Cron\Recurrence::DAILY,
                Modules\Cron\Jobs::CHECKLIST_CHECK,
                [$modules['checklist-manager'], 'runChecks']
            ),
            'blacklist-cleaner' => new Modules\Cron\Job(
                $settings['cron-jobs'],
                Modules\Cron\Job::RUN_AT_NIGHT,
                Modules\Cron\Recurrence::DAILY,
                Modules\Cron\Jobs::IP_BLACKLIST_CLEAN_UP,
                [$modules['blacklist-manager'], 'prune']
            ),
            'log-cleaner-by-age' => new Modules\Cron\Job(
                $settings['cron-jobs'],
                Modules\Cron\Job::RUN_AT_NIGHT,
                Modules\Cron\Recurrence::DAILY,
                Modules\Cron\Jobs::LOGS_CLEAN_UP_BY_AGE,
                [$modules['logger'], 'pruneByAge'],
                [$settings['log']->getMaxAge()]
            ),
            'log-cleaner-by-size' => new Modules\Cron\Job(
                $settings['cron-jobs'],
                Modules\Cron\Job::RUN_AT_NIGHT,
                Modules\Cron\Recurrence::DAILY,
                Modules\Cron\Jobs::LOGS_CLEAN_UP_BY_SIZE,
                [$modules['logger'], 'pruneBySize'],
                [$settings['log']->getMaxSize()]
            ),
            'core-checksums-verifier' => new Modules\Cron\Job(
                $settings['cron-jobs'],
                Modules\Cron\Job::RUN_AT_NIGHT,
                Modules\Cron\Recurrence::DAILY,
                Modules\Cron\Jobs::CORE_CHECKSUMS_VERIFIER,
                [$modules['core-verifier'], 'runCheck']
            ),
            'plugin-checksums-verifier' => new Modules\Cron\Job(
                $settings['cron-jobs'],
                Modules\Cron\Job::RUN_AT_NIGHT,
                Modules\Cron\Recurrence::DAILY,
                Modules\Cron\Jobs::PLUGIN_CHECKSUMS_VERIFIER,
                [$modules['plugins-verifier'], 'runCheck']
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

        // Initialize cron jobs.
        foreach ($this->cron_jobs as $cron_job) {
            $cron_job->init();
        }

        if ($this->admin) {
            // Initialize admin interface (set necessary hooks).
            $this->admin->init($this->basename)
                // Setup comes first...
                ->addPage(new Setup\AdminPage($this->settings['setup']))
                // ...then come admin pages.
                ->addPage(new Modules\Checklist\AdminPage($this->modules['checklist-manager']))
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

        // Schedule cron jobs that should be scheduled.
        foreach ($this->cron_jobs as $cron_job) {
            if ($cron_job->isOn()) {
                $cron_job->schedule();
            }
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
        // Unschedule all scheduled cron jobs.
        foreach ($this->cron_jobs as $cron_job) {
            if ($cron_job->isScheduled()) {
                $cron_job->unschedule();
            }
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

        // Remove site transients set by plugin.
        $this->wpdb->query(
            sprintf(
                "DELETE FROM {$this->wpdb->options} WHERE (option_name LIKE '%s' OR option_name LIKE '%s')",
                '_site_transient_' . Helpers\Transients::NAME_PREFIX . '%',
                '_site_transient_timeout_' . Helpers\Transients::NAME_PREFIX . '%'
            )
        );

        // Uninstall every module that requires it.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Installable) {
                $module->uninstall();
            }
        }
    }
}
