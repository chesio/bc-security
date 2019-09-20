<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Login;

use BlueChip\Security\Helpers\FormHelper;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
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

        // Section: Lockout configuration
        $this->addSettingsSection(
            'lockout-configuration',
            _x('Lockout configuration', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>';
                echo esc_html__('If failed login attempt triggers both long and short lockout at the same time, long lockout takes precedence.', 'bc-security');
                echo '</p>';
            }
        );
        $this->addSettingsField(
            Settings::SHORT_LOCKOUT_AFTER,
            __('Short lockout after', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('failed attempt(s)', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::SHORT_LOCKOUT_DURATION,
            __('Short lockout duration', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('minutes', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::LONG_LOCKOUT_AFTER,
            __('Long lockout after', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('failed attempt(s)', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::LONG_LOCKOUT_DURATION,
            __('Long lockout duration', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('hours', 'bc-security'), ]
        );
        $this->addSettingsField(
            Settings::RESET_TIMEOUT,
            __('Reset retries after', 'bc-security'),
            [FormHelper::class, 'printNumberInput'],
            [ 'append' => __('days', 'bc-security'), ]
        );

        // Section: Username blacklist
        $this->addSettingsSection(
            'username-blacklist',
            _x('Username blacklist', 'Settings section title', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('Enter any usernames that should never exist on the system. The blacklist serves two purposes:', 'bc-security') .'</p>';
                echo '<ol>';
                echo '<li>' . esc_html__('No new account can be registered with username on the blacklist.', 'bc-security') . '</li>';
                echo '<li>' . esc_html__('Every login attempt using non-existing username on the blacklist immediately triggers long lockout.', 'bc-security') . '</li>';
                echo '</ol>';
            }
        );
        $this->addSettingsField(
            Settings::USERNAME_BLACKLIST,
            __('Username blacklist', 'bc-security'),
            [FormHelper::class, 'printTextArea'],
            [ 'append' => __('Enter one username per line.', 'bc-security'), ]
        );

        // Section: Authentication cookies
        $this->addSettingsSection(
            'auth-cookies',
            _x('Auth cookies', 'Settings section title', 'bc-security')
        );
        $this->addSettingsField(
            Settings::CHECK_COOKIES,
            __('Check auth cookies', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Display generic error message on failed login
        $this->addSettingsSection(
            'generic-error-message',
            __('Display generic error message on failed login', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    /* translators: 1: link to Wikipedia page on Security through obscurity */
                    esc_html__('This is a %1$s approach, but it may make it harder for attackers to guess user credentials.', 'bc-security'),
                    '<a href="' . esc_url(__('https://en.wikipedia.org/wiki/Security_through_obscurity', 'bc-security')) . '" rel="noreferrer">' . esc_html__('security through obscurity', 'bc-security') . '</a>'
                ) . '</p>';
                echo '<p>' . esc_html__('Generic error message is displayed only for default authentication errors: invalid username, invalid email or invalid password. Display of any other authentication errors is not affected.', 'bc-security') . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::GENERIC_LOGIN_ERROR_MESSAGE,
            __('Display generic error message', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );
    }
}
