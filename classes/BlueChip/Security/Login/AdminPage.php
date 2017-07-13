<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Login;

class AdminPage extends \BlueChip\Security\Core\AdminSettingsPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-login';


    /**
     * @param Settings $settings Hardening settings
     */
    function __construct($settings)
    {
        parent::__construct($settings);

        $this->page_title = _x('Login Security Settings', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Login', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;
    }


    /**
     * Render admin page
     */
    public function render()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo $this->settings_helper->renderForm();
        echo '</div>';
    }


    /**
     * Display info message on how short and long lockouts relate to each other.
     */
    public function renderLockoutConfigurationHint()
    {
        echo '<p>';
        echo esc_html__('If failed login attempt triggers both long and short lockout at the same time, long lockout takes precedence.', 'bc-security');
        echo '</p>';
    }


    /**
     * Display info message on what is logged.
     */
    public function renderLogAndNotificationsHint()
    {
        echo '<p>';
        echo esc_html__('Both short and long lockouts are logged, if log is enabled.', 'bc-security');
        echo '</p>';
    }


    /**
     * Run on `admin_init` hook.
     */
    public function admin_init()
    {
        // Form helper is going to be useful here
        $form_helper = new \BlueChip\Security\Core\Helpers\FormHelper();

        // Shortcut
        $settings_helper = $this->settings_helper;

        // Register setting first
        $settings_helper->register();

        // Set page as current
        $settings_helper->setSettingsPage($this->slug);

        // Section: Lockout configuration
        $settings_helper->addSettingsSection(
            'lockout-configuration',
            _x('Lockout configuration', 'Settings section title', 'bc-security'),
            [$this, 'renderLockoutConfigurationHint']
        );
        $settings_helper->addSettingsField(
            Settings::SHORT_LOCKOUT_AFTER,
            __('Short lockout after', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('failed attempt(s)', 'bc-security'), ]
        );
        $settings_helper->addSettingsField(
            Settings::SHORT_LOCKOUT_DURATION,
            __('Short lockout duration', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('minutes', 'bc-security'), ]
        );
        $settings_helper->addSettingsField(
            Settings::LONG_LOCKOUT_AFTER,
            __('Long lockout after', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('failed attempt(s)', 'bc-security'), ]
        );
        $settings_helper->addSettingsField(
            Settings::LONG_LOCKOUT_DURATION,
            __('Long lockout duration', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('hours', 'bc-security'), ]
        );
        $settings_helper->addSettingsField(
            Settings::RESET_TIMEOUT,
            __('Reset retries after', 'bc-security'),
            [$form_helper, 'renderNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );
        $settings_helper->addSettingsField(
            Settings::USERNAME_BLACKLIST,
            __('Immediately (long) lock out specific usernames', 'bc-security'),
            [$form_helper, 'renderTextArea'],
            [ 'append' => __('Existing usernames are not locked even if present on the list.', 'bc-security'), ]
        );

        // Section: Authentication cookies
        $settings_helper->addSettingsSection(
            'auth-cookies',
            _x('Auth cookies', 'Settings section title', 'bc-security'),
            null
        );
        $settings_helper->addSettingsField(
            Settings::CHECK_COOKIES,
            __('Check auth cookies', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );

        // Section: Display generic error message on failed login
        $settings_helper->addSettingsSection(
            'generic-error-message',
            __('Display generic error message on failed login', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    __('This is a <a href="%s">security by obscurity</a> approach, but it may make it harder for attackers to guess user credentials.', 'bc-security'),
                    'https://en.wikipedia.org/wiki/Security_through_obscurity'
                ) . '</p>';
                echo '<p>' . esc_html__('Generic error message is displayed only for default authentication errors: invalid username, invalid email or invalid password. Display of any other authentication errors is not affected.', 'bc-security') . '</p>';
            }
        );
        $settings_helper->addSettingsField(
            Settings::GENERIC_LOGIN_ERROR_MESSAGE,
            __('Display generic error message', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
    }
}
