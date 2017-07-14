<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Setup;

class AdminPage extends \BlueChip\Security\Core\AdminSettingsPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-setup';


    /**
     * @param Settings $settings Basic settings
     */
    function __construct($settings)
    {
        parent::__construct($settings);

        $this->page_title = _x('BC Security Setup', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Setup', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;
    }


    /**
     * Render admin page
     */
    public function render()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo $this->settings_api_helper->renderForm();
        echo '</div>';
    }


    /**
     * Run on `admin_init` hook.
     */
    public function admin_init()
    {
        // Form helper is going to be useful here
        $form_helper = new \BlueChip\Security\Helpers\FormHelper();

        // Shortcut
        $settings_api_helper = $this->settings_api_helper;

        // Register setting first
        $settings_api_helper->register();

        // Set page as current
        $settings_api_helper->setSettingsPage($this->slug);

        // Section: Site connection
        $settings_api_helper->addSettingsSection(
            'site-connection',
            _x('Site connection', 'Settings section title', 'bc-security'),
            [$this, 'renderSiteConnectionHint']
        );
        $settings_api_helper->addSettingsField(
            Settings::CONNECTION_TYPE,
            __('Connection type', 'bc-security'),
            [$form_helper, 'renderSelect'],
            ['options' => $this->getConnectionOptions()]
        );
    }


    public function renderSiteConnectionHint()
    {
        $list = IpAddress::enlist(true);
        echo '<p>' . esc_html__('Your server provides following information about remote addresses:', 'bc-security') . '</p>';
        echo '<ol>';
        foreach ($list as $type => $explanation) {
            if (($ip_address = IpAddress::getRaw($type))) {
                echo '<li>' . sprintf('%s: <code>$_SERVER[<strong>%s</strong>] = <em>%s</em></code>', esc_html($explanation), $type, $ip_address) . '</li>';
            }
        }
        echo '</ol>';
    }


    private function getConnectionOptions()
    {
        $list = IpAddress::enlist(true);
        $options = [];
        foreach ($list as $type => $explanation) {
            $options[$type] = sprintf('%s: %s', $type, $explanation);
        }
        return $options;
    }
}
