<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core;

/**
 * Basis (abstract) class for every admin page with settings section.
 */
abstract class AdminSettingsPage extends AdminPage
{
    /**
     * @var \BlueChip\Security\Core\Helpers\SettingsHelper
     */
    protected $settings_helper;


    /**
     * @param \BlueChip\Security\Core\Settings $settings
     */
    function __construct($settings)
    {
        $this->settings_helper = new Helpers\SettingsHelper($settings);

        add_action('admin_init', [$this, 'admin_init']);
    }


    abstract public function admin_init();


    public function loadPage()
    {
        add_action('admin_notices', 'settings_errors');
    }
}
