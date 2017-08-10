<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Login;

use BlueChip\Security\Helpers\FormHelper;

class AdminPage extends \BlueChip\Security\Core\AdminPage
{
    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;


    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-login';


    /**
     * @param \BlueChip\Security\Modules\Login\Settings $settings Login security settings
     */
    public function __construct(Settings $settings)
    {
        $this->page_title = _x('Login Security Settings', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Login', 'Dashboard menu item name', 'bc-security');

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
     * Initialize settings page: add sections and fields.
     */
    public function init()
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current
        $this->setSettingsPage(self::SLUG);

        // Section: Lockout configuration
        $this->addSettingsSection(
            'lockout-configuration',
            _x('Lockout configuration', 'Settings section title', 'bc-security'),
            [$this, 'renderLockoutConfigurationHint']
        );
        $this->addSettingsField(
            Settings::SHORT_LOCKOUT_AFTER,
            __('Short lockout after', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('failed attempt(s)', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::SHORT_LOCKOUT_DURATION,
            __('Short lockout duration', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('minutes', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::LONG_LOCKOUT_AFTER,
            __('Long lockout after', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('failed attempt(s)', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::LONG_LOCKOUT_DURATION,
            __('Long lockout duration', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('hours', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::RESET_TIMEOUT,
            __('Reset retries after', 'bc-security'),
            [FormHelper::class, 'renderNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::USERNAME_BLACKLIST,
            __('Immediately (long) lock out specific usernames', 'bc-security'),
            [FormHelper::class, 'renderTextArea'],
            [ 'append' => __('Existing usernames are not locked even if present on the list.', 'bc-security'), ]
        );

        // Section: Authentication cookies
        $this->addSettingsSection(
            'auth-cookies',
            _x('Auth cookies', 'Settings section title', 'bc-security'),
            null
        );
        $this->addSettingsField(
            Settings::CHECK_COOKIES,
            __('Check auth cookies', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );

        // Section: Display generic error message on failed login
        $this->addSettingsSection(
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
        $this->addSettingsField(
            Settings::GENERIC_LOGIN_ERROR_MESSAGE,
            __('Display generic error message', 'bc-security'),
            [FormHelper::class, 'renderCheckbox']
        );
    }
}
