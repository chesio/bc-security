<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Helpers\FormHelper;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;


    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-notifications';


    /**
     * @param \BlueChip\Security\Modules\Notifications\Settings $settings Notifications settings
     */
    public function __construct(Settings $settings)
    {
        $this->page_title = _x('Notifications Settings', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Notifications', 'Dashboard menu item name', 'bc-security');

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

        // Set page as current.
        $this->setSettingsPage(self::SLUG);

        // Section: When to notify?
        $this->addSettingsSection(
            'when-to-notify',
            _x('When to send notification?', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('Immediately send email notification when:', 'bc-security') . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::ADMIN_USER_LOGIN,
            __('User with admin privileges logs in', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
        $this->addSettingsField(
            Settings::KNOWN_IP_LOCKOUT,
            __('Known IP address is locked out', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
        $this->addSettingsField(
            Settings::CORE_UPDATE_AVAILABLE,
            __('WordPress update is available', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
        $this->addSettingsField(
            Settings::PLUGIN_UPDATE_AVAILABLE,
            __('Plugin update is available', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
        $this->addSettingsField(
            Settings::THEME_UPDATE_AVAILABLE,
            __('Theme update is available', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
        $this->addSettingsField(
            Settings::CHECKSUMS_VERIFICATION_ERROR,
            __('Checksums verification results in error', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
        $this->addSettingsField(
            Settings::PLUGIN_DEACTIVATED,
            __('BC Security is deactivated', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );

        // Section: Who to notify?
        $this->addSettingsSection(
            'who-to-notify',
            _x('Whom to send notification?', 'Settings section title', 'bc-security')
        );
        $this->addSettingsField(
            Settings::NOTIFY_SITE_ADMIN,
            __('Notify site admin', 'bc-security'),
            [FormHelper::class, 'renderCheckbox'],
            [ 'description' => sprintf(__('Currently: %s', 'bc-security'), get_option('admin_email')), ]
        );
        $this->addSettingsField(
            Settings::NOTIFICATION_RECIPIENTS,
            __('Send notifications to:', 'bc-security'),
            [FormHelper::class, 'renderTextArea'],
            [ 'description' => __('Enter one email per line.', 'bc-security'), ]
        );
    }
}
