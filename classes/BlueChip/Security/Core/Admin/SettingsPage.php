<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Core\Admin;

/**
 * Common settings API boilerplate for admin pages.
 */
trait SettingsPage
{
    /**
     * @var \BlueChip\Security\Helpers\SettingsApiHelper
     */
    protected $settings_api_helper;


    /**
     * @param \BlueChip\Security\Core\Settings $settings
     */
    protected function constructSettingsPage($settings)
    {
        $this->settings_api_helper = new \BlueChip\Security\Helpers\SettingsApiHelper($settings);

        add_action('admin_init', [$this, 'initSettingsPage']);
    }


    protected function loadSettingsPage()
    {
        add_action('admin_notices', 'settings_errors');
    }


    public function initSettingsPage()
    {
        // Register settings.
        $this->settings_api_helper->register();

        // Init settings page.
        $this->initSettingsPageSectionsAndFields();
    }


    abstract protected function initSettingsPageSectionsAndFields();


    /**
     * Output form for settings manipulation.
     */
    protected function renderForm()
    {
        echo '<form method="post" action="' . admin_url('options.php') .'">';

        // Render nonce, action and other hidden fields...
        $this->settings_api_helper->renderSettingsFields();
        // ... visible fields ...
        $this->settings_api_helper->renderSettingsSections();
        // ... and finally the submit button :)
        submit_button();

        echo '</form>';
    }
}
