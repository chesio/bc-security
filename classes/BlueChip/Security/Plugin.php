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
    /** @var \BlueChip\Security\Admin */
    public $admin;

    /** @var array Array with module objects for all plugin modules */
    private $modules;

	/** @var array Array with setting objects for particular modules */
	private $settings;


    /**
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
	public function __construct($wpdb)
    {
		// Read plugin settings
		$this->settings = [
            // Note: when you add new settings object (key) below,
            // do not forget to add them to uninstall.php as well!
            'hardening' => new Hardening\Settings('bc-security-hardening'),
            'login'     => new Login\Settings('bc-security-login'),
            'setup'     => new Setup\Settings('bc-security-setup'),
        ];

        // Get setup info
        $setup = new Setup\Core($this->settings['setup']);

        // IP address is at core interest within this plugin :)
        $ip_address = $setup->getRemoteAddress();

        // Init admin, if necessary.
        $this->admin = is_admin() ? new Admin() : null;

        // Construct modules...
        $hardening  = new Hardening\Core($this->settings['hardening']);
        $bl_manager = new IpBlacklist\Manager($wpdb);
        $bl_bouncer = new IpBlacklist\Bouncer($ip_address, $bl_manager);
        $bookkeeper = new Login\Bookkeeper($this->settings['login'], $wpdb);
        $gatekeeper = new Login\Gatekeeper($this->settings['login'], $ip_address, $bookkeeper, $bl_manager, $bl_bouncer);

        // ... and store them for later.
        $this->modules = [
            'hardening-core'    => $hardening,
            'blacklist-manager' => $bl_manager,
            'blacklist-bouncer' => $bl_bouncer,
            'login-bookkeeper'  => $bookkeeper,
            'login-gatekeeper'  => $gatekeeper,
        ];
    }


	/**
	 * Add hooks necessary for plugin interaction with WordPress
	 */
    public function init()
    {
        // Installing?
        register_activation_hook(BC_SECURITY_PLUGIN_FILE, [$this, 'install']);

        // Init all modules
        foreach ($this->modules as $module) {
            if ($module instanceof Core\Module\Initializable) {
                $module->init();
            }
        }

        // Init admin UI, if necessary.
        if ($this->admin) {
            add_action('init', [$this, 'initAdmin'], 10, 0);
        }
	}


	/**
	 * Initialize admin menus.
	 */
	public function initAdmin()
    {
        // Init admin interface
        $this->admin->init()
            ->addPage(new Setup\AdminPage($this->settings['setup']))
            ->addPage(new Checklist\AdminPage())
            ->addPage(new Hardening\AdminPage($this->settings['hardening']))
            ->addPage(new Login\AdminPage($this->settings['login']))
            ->addPage(new IpBlacklist\AdminPage($this->modules['blacklist-manager']))
        ;
	}


    /**
     * Perform installation tasks.
     */
    public function install()
    {
        foreach ($this->modules as $module) {
            if ($module instanceof Core\Module\Installable) {
                $module->install();
            }
        }
    }
}
