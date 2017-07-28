<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Notifications;

class AdminPage extends \BlueChip\Security\Core\AdminSettingsPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-notifications';


    /**
     * @param \BlueChip\Security\Modules\Login\Settings $settings Notifications settings
     */
    function __construct($settings)
    {
        parent::__construct($settings);

        $this->page_title = _x('Notifications Settings', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Notifications', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;
    }


    /**
     * Render admin page.
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
        // Form helper is going to be useful here.
        $form_helper = new \BlueChip\Security\Helpers\FormHelper();

        // Shortcut
        $settings_api_helper = $this->settings_api_helper;

        // Register setting first.
        $settings_api_helper->register();

        // Set page as current.
        $settings_api_helper->setSettingsPage($this->slug);

        // Section: When to notify?
        $settings_api_helper->addSettingsSection(
            'when-to-notify',
            _x('When to send notification?', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('Immediately send email notification when:', 'bc-security') . '</p>';
            }
        );
        $settings_api_helper->addSettingsField(
            Settings::ADMIN_USER_LOGIN,
            __('User with admin privileges logs in', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
        $settings_api_helper->addSettingsField(
            Settings::KNOWN_IP_LOCKOUT,
            __('Known IP address is locked out', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
        $settings_api_helper->addSettingsField(
            Settings::CORE_UPDATE_AVAILABLE,
            __('WordPress update is available', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
        $settings_api_helper->addSettingsField(
            Settings::PLUGIN_UPDATE_AVAILABLE,
            __('Plugin update is available', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
        $settings_api_helper->addSettingsField(
            Settings::THEME_UPDATE_AVAILABLE,
            __('Theme update is available', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
        $settings_api_helper->addSettingsField(
            Settings::CHECKSUMS_VERIFICATION_ERROR,
            __('Checksums verification results in error', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
        $settings_api_helper->addSettingsField(
            Settings::PLUGIN_DEACTIVATED,
            __('BC Security is deactivated', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );

        // Section: Who to notify?
        $settings_api_helper->addSettingsSection(
            'who-to-notify',
            _x('Whom to send notification?', 'Settings section title', 'bc-security')
        );
        $settings_api_helper->addSettingsField(
            Settings::NOTIFY_SITE_ADMIN,
            __('Notify site admin', 'bc-security'),
            [$form_helper, 'renderCheckbox'],
            [ 'description' => sprintf(__('Currently: %s', 'bc-security'), get_option('admin_email')), ]
        );
        $settings_api_helper->addSettingsField(
            Settings::NOTIFICATION_RECIPIENTS,
            __('Send notifications to:', 'bc-security'),
            [$form_helper, 'renderTextArea'],
            [ 'description' => __('Enter one email per line.', 'bc-security'), ]
        );
    }
}
