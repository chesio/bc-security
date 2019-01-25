<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Hardening;

use BlueChip\Security\Helpers\FormHelper;
use BlueChip\Security\Helpers\HaveIBeenPwned;

class AdminPage extends \BlueChip\Security\Core\Admin\AbstractPage
{
    /** Page has settings section */
    use \BlueChip\Security\Core\Admin\SettingsPage;


    /**
     * @var string Page slug
     */
    const SLUG = 'bc-security-hardening';


    /**
     * @param \BlueChip\Security\Modules\Hardening\Settings $settings Hardening settings
     */
    public function __construct(Settings $settings)
    {
        $this->page_title = _x('WordPress Hardening', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Hardening', 'Dashboard menu item name', 'bc-security');

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
        echo '<p>' . esc_html__('All security features below are applied through WordPress filters.', 'bc-security') . '</p>';
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

        // Set page as current.
        $this->setSettingsPage(self::SLUG);

        // Section: Disable pingbacks
        $this->addSettingsSection(
            'disable-pingback',
            __('Disable pingbacks', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    __('<strong>Pingbacks</strong> can be <a href="%s">misused for DDoS attacks</a>. Although the "Allow link notifications from other blogs" setting can be used to disable them, it does not affect configuration of existing posts.', 'bc-security'),
                    'http://blog.sucuri.net/2014/03/more-than-162000-wordpress-sites-used-for-distributed-denial-of-service-attack.html'
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
                echo '<p>' . sprintf(
                    __('Disabling methods that require <strong>XML-RPC authentication</strong> helps to prevent <a href="%s">brute force attacks</a>.', 'bc-security'),
                    'https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html'
                ) . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::DISABLE_XML_RPC,
            __('Disable XML-RPC methods', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Disable REST API to anonymous users
        $this->addSettingsSection(
            'disable-rest-api',
            __('Disable access to REST API to anonymous users', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    __('<a href="%1$s">REST API</a> is a powerful feature, but soon after its introduction to WordPress it proved to also be <a href="%2$s">yet another attack surface</a>. By enabling this option, an authentication error is forcibly returned to any REST API requests from sources who are not logged into your website.', 'bc-security'),
                    'https://developer.wordpress.org/rest-api/',
                    'https://blog.sucuri.net/2017/02/content-injection-vulnerability-wordpress-rest-api.html'
                ) . '</p>';
            }
        );
        $this->addSettingsField(
            Settings::DISABLE_REST_API,
            __('Disable REST API access', 'bc-security'),
            [FormHelper::class, 'printCheckbox']
        );

        // Section: Check/validate user passwords against Pwned Passwords database
        $this->addSettingsSection(
            'pwned-passwords',
            __('Validate user passwords against Pwned Passwords database', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    __('<a href="%1$s">Pwned Passwords</a> is a large database of passwords previously exposed in data breaches. This exposure makes them unsuitable for ongoing use as they are at much greater risk of being used to take over other accounts.', 'bc-security'),
                    HaveIBeenPwned::PWNEDPASSWORDS_HOME_URL
                ) . '</p>';
                echo '<p>' . __('BC Security allows you to utilize this database in two ways:', 'bc-security');
                echo '<ol>';
                echo '<li>' . __("When <strong>password validation</strong> is enabled, passwords are checked against the Pwned Passwords database when new user is being created or existing user's password is being changed via profile update page or through password reset form. If there is a match, the operation is aborted with an error message asking for a different password.", 'bc-security') . '</li>';
                echo '<li>' . __("When <strong>password check</strong> is enabled, passwords are checked against the Pwned Passwords database when user logs in to the backend. If there is a match, a non-dismissible warning is displayed on all back-end pages encouraging the user to change its password.", 'bc-security') . '</li>';
                echo '</ol>';
                echo '<p>' . sprintf(
                    __('Important: Only the first 5 characters of SHA-1 hash of the actual password are ever shared with Pwned Passwords service. See <a href="%s">Pwned Passwords API documentation</a> for more details.', 'bc-security'),
                    'https://haveibeenpwned.com/API/v2#PwnedPasswords'
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
