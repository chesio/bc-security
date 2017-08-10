<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Setup;

use BlueChip\Security\Helpers\FormHelper;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;


    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-setup';


    /**
     * @param \BlueChip\Security\Setup\Settings $settings Basic settings
     */
    public function __construct(Settings $settings)
    {
        $this->page_title = _x('BC Security Setup', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Setup', 'Dashboard menu item name', 'bc-security');

        $this->useSettings($settings);
    }


    public function loadPage()
    {
        $this->displaySettingsErrors();
    }


    /**
     * Render admin page.
     */
    public function render()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo $this->renderSettingsForm();
        echo '</div>';
    }


    /**
     * Initialize settings page: add sections and fields.
     */
    public function init()
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current
        $this->setSettingsPage(self::SLUG);

        // Section: Site connection
        $this->addSettingsSection(
            'site-connection',
            _x('Site connection', 'Settings section title', 'bc-security'),
            [$this, 'renderSiteConnectionHint']
        );
        $this->addSettingsField(
            Settings::CONNECTION_TYPE,
            __('Connection type', 'bc-security'),
            [FormHelper::class, 'renderSelect'],
            ['options' => $this->getConnectionOptions()]
        );
    }


    public function renderSiteConnectionHint()
    {
        $list = IpAddress::enlist(true);

        echo '<p>';
        echo esc_html__('Your server provides following information about remote addresses:', 'bc-security');
        echo '</p>';

        echo '<ol>';
        foreach ($list as $type => $explanation) {
            if (($ip_address = IpAddress::getRaw($type))) {
                echo '<li>' . sprintf('%s: <code>$_SERVER[<strong>%s</strong>] = <em>%s</em></code>', esc_html($explanation), $type, $ip_address) . '</li>';
            }
        }
        echo '</ol>';
    }


    /**
     * Return available connection options in format suitable for <select> field.
     *
     * @return array
     */
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
