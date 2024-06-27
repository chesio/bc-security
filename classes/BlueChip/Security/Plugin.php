<?php

declare(strict_types=1);

namespace BlueChip\Security;

use wpdb;

/**
 * Main plugin class
 */
class Plugin
{
    /**
     * @var Modules Plugin module objects
     */
    private Modules $modules;

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
        $this->modules = new Modules($wpdb, $this->remote_address, $setup->getServerAddress(), $settings);
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
            add_action('init', $this->init(...), 10, 0);
        }
    }


    /**
     * Perform initialization tasks.
     * Method should be run (early) in init hook.
     *
     * @action https://developer.wordpress.org/reference/hooks/init/
     */
    private function init(): void
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
                    $this->modules->getChecklistManager(),
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
                    $this->modules->getHtaccessSynchronizer()
                ))
                ->addPage(new Modules\InternalBlocklist\AdminPage(
                    $this->modules->getInternalBlocklistManager(),
                    $this->modules->getHtaccessSynchronizer(),
                    $this->modules->getCronJobManager()
                ))
                ->addPage(new Modules\ExternalBlocklist\AdminPage(
                    $this->settings->forExternalBlocklist(),
                    $this->modules->getExternalBlocklistManager()
                ))
                ->addPage(new Modules\Notifications\AdminPage(
                    $this->settings->forNotifications()
                ))
                ->addPage(new Modules\Log\AdminPage(
                    $this->settings->forLog(),
                    $this->modules->getLogger()
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
        // Make sure plugin related options are autoloaded when plugin is active.
        foreach ($this->settings as $settings) {
            $settings->setAutoload(true);
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

        // Make sure plugin related options are *not* autoloaded when plugin is inactive.
        foreach ($this->settings as $settings) {
            $settings->setAutoload(false);
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
