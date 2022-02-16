<?php

namespace BlueChip\Security;

/**
 * Main plugin class
 */
class Plugin
{
    /**
     * @var array Plugin module objects
     */
    private $modules;

    /**
     * @var array Plugin setting objects
     */
    private $settings;

    /**
     * @var string Plugin filename
     */
    private $plugin_filename;

    /**
     * @var \wpdb WordPress database access abstraction object
     */
    private $wpdb;


    /**
     * Construct the plugin instance.
     *
     * @param string $plugin_filename Plugin filename
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(string $plugin_filename, \wpdb $wpdb)
    {
        $this->plugin_filename = $plugin_filename;
        $this->wpdb = $wpdb;

        // Read plugin settings.
        $this->settings = $settings = self::constructSettings();

        // Get setup info.
        $setup = new Setup\Core($settings['setup']);

        // Construct modules.
        $this->modules = self::constructModules($wpdb, $setup->getRemoteAddress(), $setup->getServerAddress(), $settings);
    }


    /**
     * Construct plugin settings.
     *
     * @return array
     */
    private static function constructSettings(): array
    {
        return [
            'cron-jobs'         => new Modules\Cron\Settings('bc-security-cron-jobs'),
            'checklist-autorun' => new Modules\Checklist\AutorunSettings('bc-security-checklist-autorun'),
            'hardening'         => new Modules\Hardening\Settings('bc-security-hardening'),
            'log'               => new Modules\Log\Settings('bc-security-log'),
            'login'             => new Modules\Login\Settings('bc-security-login'),
            'notifications'     => new Modules\Notifications\Settings('bc-security-notifications'),
            'setup'             => new Setup\Settings('bc-security-setup'),
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
    private static function constructModules(\wpdb $wpdb, string $remote_address, string $server_address, array $settings): array
    {
        $google_api = new Setup\GoogleAPI($settings['setup']);

        $hostname_resolver  = new Modules\Services\ReverseDnsLookup\Resolver();
        $cron_job_manager   = new Modules\Cron\Manager($settings['cron-jobs']);
        $logger             = new Modules\Log\Logger($wpdb, $remote_address, $settings['log'], $hostname_resolver);
        $checklist_manager  = new Modules\Checklist\Manager($settings['checklist-autorun'], $cron_job_manager, $wpdb, $google_api->getKey());
        $monitor            = new Modules\Log\EventsMonitor($remote_address, $server_address);
        $notifier           = new Modules\Notifications\Watchman($settings['notifications'], $remote_address, $logger);
        $hardening          = new Modules\Hardening\Core($settings['hardening']);
        $blacklist_manager  = new Modules\IpBlacklist\Manager($wpdb);
        $blacklist_bouncer  = new Modules\IpBlacklist\Bouncer($remote_address, $blacklist_manager);
        $bookkeeper         = new Modules\Login\Bookkeeper($settings['login'], $wpdb);
        $gatekeeper         = new Modules\Login\Gatekeeper($settings['login'], $remote_address, $bookkeeper, $blacklist_manager);

        return [
            'cron-job-manager'  => $cron_job_manager,
            'hostname-resolver' => $hostname_resolver,
            'logger'            => $logger,
            'checklist-manager' => $checklist_manager,
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
     * Load the plugin by hooking into WordPress actions and filters.
     * Method should be invoked immediately on plugin load.
     */
    public function load(): void
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
     *
     * @action https://developer.wordpress.org/reference/hooks/init/
     */
    public function init(): void
    {
        // Initialize all modules that require initialization.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Initializable) {
                $module->init();
            }
        }

        if (is_admin()) {
            $assets_manager = new Core\AssetsManager($this->plugin_filename);

            // Initialize admin interface (set necessary hooks).
            (new Admin())->init($this->plugin_filename)
                // Setup comes first...
                ->addPage(new Setup\AdminPage(
                    $this->settings['setup']
                ))
                // ...then come admin pages.
                ->addPage(new Modules\Checklist\AdminPage(
                    $this->modules['checklist-manager'],
                    $this->settings['checklist-autorun'],
                    $assets_manager
                ))
                ->addPage(new Modules\Hardening\AdminPage(
                    $this->settings['hardening']
                ))
                ->addPage(new Modules\Login\AdminPage(
                    $this->settings['login']
                ))
                ->addPage(new Modules\IpBlacklist\AdminPage(
                    $this->modules['blacklist-manager'],
                    $this->modules['cron-job-manager']
                ))
                ->addPage(new Modules\Notifications\AdminPage(
                    $this->settings['notifications']
                ))
                ->addPage(new Modules\Log\AdminPage(
                    $this->settings['log'],
                    $this->modules['logger']
                ))
                ->addPage(new Modules\Tools\AdminPage($this->settings))
            ;
        }
    }


    /**
     * Perform activation and installation tasks.
     * Method should be run on plugin activation.
     *
     * @link https://developer.wordpress.org/plugins/the-basics/activation-deactivation-hooks/
     */
    public function activate(): void
    {
        // Explicitly persist every setting object, so related option is autoloaded.
        foreach ($this->settings as $settings) {
            $settings->persist();
        }

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
    }


    /**
     * Perform deactivation tasks.
     * Method should be run on plugin deactivation.
     *
     * @link https://developer.wordpress.org/plugins/the-basics/activation-deactivation-hooks/
     */
    public function deactivate(): void
    {
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
    public function uninstall(): void
    {
        // Remove plugin settings.
        foreach ($this->settings as $settings) {
            $settings->destroy();
        }

        // Remove site transients set by plugin.
        Helpers\Transients::flush($this->wpdb);

        // Uninstall every module that requires it.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Installable) {
                $module->uninstall();
            }
        }
    }
}
