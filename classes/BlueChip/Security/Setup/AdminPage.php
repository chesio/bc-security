<?php

namespace BlueChip\Security\Setup;

use BlueChip\Security\Helpers\AdminNotices;
use BlueChip\Security\Helpers\FormHelper;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;


    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-setup';


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

        if (!empty($connection_type = Core::getConnectionType())) {
            // Connection type is set via constant.
            AdminNotices::add(
                \sprintf(
                    __('You have set <code>BC_SECURITY_CONNECTION_TYPE</code> to <code>%s</code>, therefore the setting below is ignored.', 'bc-security'),
                    $connection_type
                ),
                AdminNotices::WARNING,
                false, // ~ not dismissible
                false // ~ do not escape HTML
            );
        }

        if (!empty($google_api_key = GoogleAPI::getStaticKey())) {
            // Google API key is set via constant.
            AdminNotices::add(
                \sprintf(
                    __('You have configured <code>BC_SECURITY_GOOGLE_API_KEY</code> to <code>%s</code>, therefore the Google API key setting below is ignored.', 'bc-security'),
                    $google_api_key
                ),
                AdminNotices::WARNING,
                false, // ~ not dismissible
                false // ~ do not escape HTML
            );
        }
    }


    /**
     * Output page contents.
     */
    public function printContents()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        $this->printSettingsForm();
        echo '</div>';
    }


    /**
     * Initialize settings page: add sections and fields.
     */
    public function initPage()
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current
        $this->setSettingsPage(self::SLUG);

        // Section: Site connection
        $this->addSettingsSection(
            'site-connection',
            _x('Site connection', 'Settings section title', 'bc-security'),
            [$this, 'printSiteConnectionHint']
        );
        $this->addSettingsField(
            Settings::CONNECTION_TYPE,
            __('Connection type', 'bc-security'),
            [FormHelper::class, 'printSelect'],
            ['options' => $this->getConnectionOptions()]
        );

        // Section: Google API key
        $this->addSettingsSection(
            'google-api',
            _x('Google API key', 'Settings section title', 'bc-security'),
            [$this, 'printGoogleAPIHint']
        );
        $this->addSettingsField(
            Settings::GOOGLE_API_KEY,
            __('Google API key', 'bc-security'),
            [FormHelper::class, 'printTextInput']
        );
    }


    public function printGoogleAPIHint()
    {
        echo '<p>';
        echo sprintf(
            /* translators: 1: link to Google Safe Browsing "Get Started" page, 2: link to Google Safe Browsing page */
            esc_html__('%1$s is required only if you would like to check your website against the %2$s.', 'bc-security'),
            '<a href="' . esc_url('https://developers.google.com/safe-browsing/v4/get-started') . '" rel="noreferrer">' . esc_html__('Google API key', 'bc-security') . '</a>',
            '<a href="' . esc_url('https://developers.google.com/safe-browsing/') . '" rel="noreferrer">' . esc_html__('Google Safe Browsing lists of unsafe web resources', 'bc-security') . '</a>'
        );
        echo '</p>';
    }


    public function printSiteConnectionHint()
    {
        $list = IpAddress::enlist(true);

        echo '<p>';
        echo esc_html__('Your server provides following information about remote addresses:', 'bc-security');
        echo '</p>';

        echo '<ol>';
        foreach ($list as $type => $explanation) {
            if (($ip_address = IpAddress::getRaw($type))) {
                echo '<li>' . \sprintf('%s: <code>$_SERVER[<strong>%s</strong>] = <em>%s</em></code>', esc_html($explanation), $type, $ip_address) . '</li>';
            }
        }
        echo '</ol>';
    }


    /**
     * Return available connection options in format suitable for <select> field.
     *
     * @return array
     */
    private function getConnectionOptions(): array
    {
        $list = IpAddress::enlist(true);
        $options = [];
        foreach ($list as $type => $explanation) {
            $options[$type] = \sprintf('%s: %s', $type, $explanation);
        }
        return $options;
    }
}
