<?php

namespace BlueChip\Security;

use wpdb;

/**
 * Main plugin class
 */
class Plugin
{
    /**
     * @var array<string,object> Plugin module objects
     */
    private array $modules;

    /**
     * @var Settings Plugin settings object
     */
    private Settings $settings;

    /**
     * @var string Plugin filename
     */
    private string $plugin_filename;

    /**
     * @var string Remote address
     */
    private string $remote_address;

    /**
     * @var wpdb WordPress database access abstraction object
     */
    private wpdb $wpdb;


    /**
     * Construct the plugin instance.
     *
     * @param string $plugin_filename Plugin filename
     * @param wpdb $wpdb WordPress database access abstraction object
     */
    public function __construct(string $plugin_filename, wpdb $wpdb)
    {
        $this->plugin_filename = $plugin_filename;
        $this->wpdb = $wpdb;

        // Read plugin settings.
        $this->settings = $settings = new Settings();

        // Get setup info.
        $setup = new Setup\Core($settings->forSetup());

        // Get remote address.
        $this->remote_address = $setup->getRemoteAddress();

        // Construct modules.
        $this->modules = self::constructModules($wpdb, $this->remote_address, $setup->getServerAddress(), $settings);
    }


    /**
     * Construct plugin modules.
     *
     * @param \wpdb $wpdb
     * @param string $remote_address
     * @param string $server_address
     * @param Settings $settings
     *
     * @return array<string,object>
     */
    private static function constructModules(wpdb $wpdb, string $remote_address, string $server_address, Settings $settings): array
    {
        $google_api = new Setup\GoogleAPI($settings->forSetup());

        $hostname_resolver          = new Modules\Services\ReverseDnsLookup\Resolver();
        $cron_job_manager           = new Modules\Cron\Manager($settings->forCronJobs());
        $logger                     = new Modules\Log\Logger($wpdb, $remote_address, $settings->forLog(), $hostname_resolver);
        $checklist_manager          = new Modules\Checklist\Manager($settings->forChecklistAutorun(), $cron_job_manager, $wpdb, $google_api->getKey());
        $monitor                    = new Modules\Log\EventsMonitor($remote_address, $server_address);
        $notifier                   = new Modules\Notifications\Watchman($settings->forNotifications(), $remote_address, $logger);
        $hardening                  = new Modules\Hardening\Core($settings->forHardening());
        $htaccess_synchronizer      = new Modules\InternalBlocklist\HtaccessSynchronizer();
        $internal_blocklist_manager = new Modules\InternalBlocklist\Manager($wpdb, $htaccess_synchronizer);
        $external_blocklist_manager = new Modules\ExternalBlocklist\Manager($settings->forExternalBlocklist(), $cron_job_manager);
        $bad_requests_banner        = new Modules\BadRequestsBanner\Core($remote_address, $server_address, $settings->forBadRequestsBanner(), $internal_blocklist_manager);
        $access_bouncer             = new Modules\Access\Bouncer($remote_address, $internal_blocklist_manager, $external_blocklist_manager);
        $bookkeeper                 = new Modules\Login\Bookkeeper($settings->forLogin(), $wpdb);
        $gatekeeper                 = new Modules\Login\Gatekeeper($settings->forLogin(), $remote_address, $bookkeeper, $internal_blocklist_manager, $access_bouncer);

        return [
            'cron-job-manager'              => $cron_job_manager,
            'hostname-resolver'             => $hostname_resolver,
            'logger'                        => $logger,
            'checklist-manager'             => $checklist_manager,
            'events-monitor'                => $monitor,
            'notifier'                      => $notifier,
            'hardening-core'                => $hardening,
            'htaccess-synchronizer'         => $htaccess_synchronizer,
            'internal-blocklist-manager'    => $internal_blocklist_manager,
            'external-blocklist-manager'    => $external_blocklist_manager,
            'bad-requests-banner'           => $bad_requests_banner,
            'access-bouncer'                => $access_bouncer,
            'login-bookkeeper'              => $bookkeeper,
            'login-gatekeeper'              => $gatekeeper,
        ];
    }


    /**
     * Load the plugin by hooking into WordPress actions and filters.
     *
     * @action https://developer.wordpress.org/reference/hooks/plugins_loaded/
     */
    public function load(): void
    {
        // Plugin functionality relies heavily on knowledge of remote address,
        // so die immediately if remote address is unknown (except in case of cli context).
        if (($this->remote_address === '') && !Helpers\Is::cli()) {
            Helpers\Utils::blockAccessTemporarily();
        }

        // Load all modules that require immediate loading.
        foreach ($this->modules as $module) {
            if ($module instanceof Modules\Loadable) {
                $module->load();
            }
        }

        // Run initialization if `init` hook has been fired already, otherwise just hook the init method to it.
        if (did_action('init')) {
            $this->init();
        } else {
            add_action('init', [$this, 'init'], 10, 0);
        }
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
                    $this->settings->forSetup()
                ))
                // ...then come admin pages.
                ->addPage(new Modules\Checklist\AdminPage(
                    $this->modules['checklist-manager'],
                    $this->settings->forChecklistAutorun(),
                    $assets_manager
                ))
                ->addPage(new Modules\Hardening\AdminPage(
                    $this->settings->forHardening()
                ))
                ->addPage(new Modules\Login\AdminPage(
                    $this->settings->forLogin()
                ))
                ->addPage(new Modules\BadRequestsBanner\AdminPage(
                    $this->settings->forBadRequestsBanner(),
                    $this->modules['htaccess-synchronizer']
                ))
                ->addPage(new Modules\InternalBlocklist\AdminPage(
                    $this->modules['internal-blocklist-manager'],
                    $this->modules['htaccess-synchronizer'],
                    $this->modules['cron-job-manager']
                ))
                ->addPage(new Modules\ExternalBlocklist\AdminPage(
                    $this->settings->forExternalBlocklist(),
                    $this->modules['external-blocklist-manager']
                ))
                ->addPage(new Modules\Notifications\AdminPage(
                    $this->settings->forNotifications()
                ))
                ->addPage(new Modules\Log\AdminPage(
                    $this->settings->forLog(),
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
