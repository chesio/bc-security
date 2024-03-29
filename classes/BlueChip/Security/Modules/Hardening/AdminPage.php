<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Hardening;

use BlueChip\Security\Core\Admin\AbstractPage;
use BlueChip\Security\Core\Admin\SettingsPage;
use BlueChip\Security\Helpers\AdminNotices;
use BlueChip\Security\Helpers\FormHelper;
use BlueChip\Security\Helpers\HaveIBeenPwned;
use BlueChip\Security\Modules\Log\AdminPage as LogAdminPage;
use BlueChip\Security\Modules\Log\Events\LoginFailure;

class AdminPage extends AbstractPage
{
    /** Page has settings section */
    use SettingsPage;


    /**
     * @var string Page slug
     */
    public const SLUG = 'bc-security-hardening';


    /**
     * @param Settings $settings Hardening settings
     */
    public function __construct(Settings $settings)
    {
        $this->page_title = _x('WordPress Hardening', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Hardening', 'Dashboard menu item name', 'bc-security');

        $this->useSettings($settings);
    }


    protected function loadPage(): void
    {
        $this->displaySettingsErrors();

        // Show warning if both default login options are disabled at the same time.
        if ($this->settings[Settings::DISABLE_LOGIN_WITH_EMAIL] && $this->settings[Settings::DISABLE_LOGIN_WITH_USERNAME]) {
            AdminNotices::add(__('Both default login options (login with email and login with username) are disabled. Make sure there is an alternative login option available.', 'bc-security'), AdminNotices::WARNING);
        }
    }


    /**
     * Output page contents.
     */
    public function printContents(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>' . esc_html__('All security features below are applied through WordPress filters.', 'bc-security') . '</p>';
        $this->printSettingsForm();
        echo '</div>';
    }


    /**
     * Initialize settings page: add sections and fields.
     */
    public function initPage(): void
    {
        // Register settings.
        $this->registerSettings();

        // Set page as current.
        $this->setSettingsPage(self::SLUG);

        // Section: Disable pingbacks
        $this->addSettingsSection(
            'disable-pingback',
            __('Disable pingbacks', 'bc-security'),
            function () {
                echo '<p>' . \sprintf(
                    /* translators: 1: Pingbacks label, 2: link to Sucuri Blog article on DDOSing via pingbacks */
                    esc_html__('%1$s can be %2$s. Although the "Allow link notifications from other blogs" setting can be used to disable them, it does not affect configuration of existing posts.', 'bc-security'),
                    '<strong>' . esc_html__('Pingbacks', 'bc-security') . '</strong>',
                    '<a href="https://blog.sucuri.net/2014/03/more-than-162000-wordpress-sites-used-for-distributed-denial-of-service-attack.html" rel="noreferrer">' . esc_html__('misused for DDoS attacks', 'bc-security') . '</a>'
                ) . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::DISABLE_PINGBACKS,
            __('Disable pingbacks', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Disable XML-RPC methods that require authentication
        $this->addSettingsSection(
            'disable-xml-rpc',
            __('Disable XML-RPC methods that require authentication', 'bc-security'),
            function () {
                echo '<p>' . \sprintf(
                    /* translators: 1: XML-RPC authentication label, 2: link to Sucuri Blog article on brute force amplification attacks */
                    esc_html__('Disabling methods that require %1$s helps to prevent %2$s.', 'bc-security'),
                    '<strong>' . esc_html__('XML-RPC authentication', 'bc-security') . '</strong>',
                    '<a href="https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html" rel="noreferrer">' . esc_html__('brute force attacks', 'bc-security') . '</a>'
                ) . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::DISABLE_XML_RPC,
            __('Disable XML-RPC methods', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Disable application passwords
        if (function_exists('wp_is_application_passwords_available')) { // WP 5.6 and newer
            $this->addSettingsSection(
                'disable-application-passwords',
                __('Disable application passwords', 'bc-security'),
                function () {
                    echo '<p>' . \sprintf(
                        /* translators: 1: Application passwords label, 2: link to Wordfence blog article on risks of application passwords */
                        esc_html__('%1$s feature allows external applications to request permission to connect to a site and generate a password specific to that application. Once the application has been granted access, it can perform actions on behalf of a user via the WordPress REST API. Unfortunately, the way this feature is implemented also provides %2$s, so it is advised to disable it when it is not used.', 'bc-security'),
                        '<strong>' . esc_html__('Application passwords', 'bc-security') . '</strong>',
                        '<a href="https://www.wordfence.com/blog/2020/12/wordpress-5-6-introduces-a-new-risk-to-your-site-what-to-do/" rel="noreferrer">' . esc_html__('yet another attack vector', 'bc-security') . '</a>'
                    ) . '</p>';
                }
            );
            $this->addSettingsField(
                Settings::DISABLE_APPLICATION_PASSWORDS,
                __('Disable application passwords', 'bc-security'),
                [FormHelper::class, 'printCheckbox']
            );
        }

        // Section: Disable usernames discovery
        $this->addSettingsSection(
            'disable-usernames-discovery',
            __('Disable usernames discovery', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('There are two ways for anonymous users to find out a list of usernames on your website:', 'bc-security') . '</p>';
                echo '<ol>';
                echo '<li>' . \sprintf(
                    /* translators: 1: link to wp/users REST API endpoint */
                    esc_html__('Through %1$s REST API endpoint', 'bc-security'),
                    '<a href="https://developer.wordpress.org/rest-api/reference/users/#list-users" rel="noreferrer"><code>wp/users</code></a>'
                ) . '</li>';
                echo '<li>' . \sprintf(
                    /* translators: 1: link to blog article explaining username enumeration attack */
                    esc_html__('Via %1$s technique', 'bc-security'),
                    '<a href="https://hackertarget.com/wordpress-user-enumeration/" rel="noreferrer">' . esc_html__('username enumeration', 'bc-security') . '</a>'
                ) . '</li>';
                echo '</ol>';
                echo '<p>' . esc_html__('This feature disables both of them.', 'bc-security') . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::DISABLE_USERNAMES_DISCOVERY,
            __('Disable usernames discovery', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Disable login with username or email
        $this->addSettingsSection(
            'restrict-login-options',
            __('Restrict login options', 'bc-security'),
            function () {
                echo '<p>' . esc_html__('WordPress by default allows users to log in with either their username or email. Disabling either of these two options can reduce risk from certain automated attacks:', 'bc-security') . '</p>';
                echo '<ol>';
                echo '<li>' . sprintf(
                    /* translators: 1: Mitigation option title, 2: link to OWASP cheat sheet page on "Credential stuffing mitigation", 3: link to OWASP page on credential stuffing attacks */
                    esc_html__('%1$s %2$s from %3$s.', 'bc-security'),
                    '<strong>' . esc_html__('Disabling login with email', 'bc-security') . '</strong>',
                    '<a href="https://cheatsheetseries.owasp.org/cheatsheets/Credential_Stuffing_Prevention_Cheat_Sheet.html#require-unpredictable-usernames" rel="noreferrer">' . esc_html__('reduces risk', 'bc-security') . '</a>',
                    '<a href="https://owasp.org/www-community/attacks/Credential_stuffing" rel="noreferrer">' . esc_html__('credential stuffing attacks', 'bc-security') . '</a>'
                ) . '</li>';
                echo '<li>' . sprintf(
                    /* translators: 1: Mitigation option title */
                    esc_html__('%1$s renders brute-force attacks targeting existing usernames harmless.', 'bc-security'),
                    '<strong>' . esc_html__('Disabling login with username', 'bc-security') . '</strong>',
                ) . '</li>';
                echo '</ol>';
                echo '<p>' . sprintf(
                    /* translators: 1: link to failed logins page */
                    esc_html__('Examining %1$s may help to find out whether any of the options above makes sense for your website.', 'bc-security'),
                    '<a href="' . LogAdminPage::getPageUrl(LoginFailure::ID) . '">' . esc_html__('failed login logs', 'bc-security') . '</a>'
                ) . '</p>';
                echo '<p><strong>' . esc_html__('Caution: You most likely do not want to disable both options at the same time unless there is an alternative login method provided!', 'bc-security') . '</strong></p>';
            }
        );
        $this->addSettingsField(
            Settings::DISABLE_LOGIN_WITH_EMAIL,
            __('Disable login with email', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );
        $this->addSettingsField(
            Settings::DISABLE_LOGIN_WITH_USERNAME,
            __('Disable login with username', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Check/validate user passwords against Pwned Passwords database
        $this->addSettingsSection(
            'pwned-passwords',
            __('Validate user passwords against Pwned Passwords database', 'bc-security'),
            function () {
                echo '<p>' . \sprintf(
                    /* translators: 1: link to Pwned Passwords homepage */
                    esc_html__('%1$s is a large database of passwords previously exposed in data breaches. This exposure makes them unsuitable for ongoing use as they are at much greater risk of being used to take over other accounts.', 'bc-security'),
                    '<a href="' . HaveIBeenPwned::PWNEDPASSWORDS_HOME_URL . '" rel="noreferrer">Pwned Passwords</a>'
                ) . '</p>';
                echo '<p>' . esc_html__('BC Security allows you to utilize this database in two ways:', 'bc-security');
                echo '<ol>';
                echo '<li>' . \sprintf(
                    /* translators: 1: password validation label */
                    esc_html__('When %1$s is enabled, passwords are checked against the Pwned Passwords database when new user is being created or existing user\'s password is being changed via profile update page or through password reset form. If there is a match, the operation is aborted with an error message asking for a different password.', 'bc-security'),
                    '<strong>' . esc_html__('password validation', 'bc-security') . '</strong>'
                ) . '</li>';
                echo '<li>' . \sprintf(
                    /* translators: 1: password check label */
                    esc_html__('When %1$s is enabled, passwords are checked against the Pwned Passwords database when user logs in to the backend. If there is a match, a non-dismissible warning is displayed on all back-end pages encouraging the user to change its password.', 'bc-security'),
                    '<strong>' . esc_html__('password check', 'bc-security') . '</strong>'
                ) . '</li>';
                echo '</ol>';
                echo '<p>' . \sprintf(
                    /* translators: 1: link to Pwned Passwords API documentation */
                    esc_html__('Important: Only the first 5 characters of SHA-1 hash of the actual password are ever shared with Pwned Passwords service. See %1$s for more details.', 'bc-security'),
                    '<a href="https://haveibeenpwned.com/API/v2#PwnedPasswords" rel="noreferrer">' . esc_html__('Pwned Passwords API documentation', 'bc-security') . '</a>'
                ) . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::VALIDATE_PASSWORDS,
            __('Validate passwords on user creation or password change', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );
        $this->addSettingsField(
            Settings::CHECK_PASSWORDS,
            __('Check passwords of existing users', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );
    }
}
