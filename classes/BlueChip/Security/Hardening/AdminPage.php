<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Hardening;

class AdminPage extends \BlueChip\Security\Core\AdminSettingsPage
{
    /** @var string Page slug */
    const SLUG = 'bc-security-hardening';

    /**
     * @param Settings $settings Hardening settings
     */
    function __construct($settings)
    {
        parent::__construct($settings);

        $this->page_title = _x('WordPress Hardening', 'Dashboard page title', 'bc-security');
        $this->menu_title = _x('Hardening', 'Dashboard menu item name', 'bc-security');
        $this->slug = self::SLUG;
    }


    /**
     * Render admin page
     */
    public function render()
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->page_title) . '</h1>';
        echo '<p>' . esc_html__('All security features below are applied through WordPress filters.', 'bc-security') . '</p>';
        echo $this->settings_helper->renderForm();
        echo '</div>';
    }


    /**
     * Run on `admin_init` hook.
     */
    public function admin_init()
    {
        // Form helper is going to be useful here
        $form_helper = new \BlueChip\Security\Core\Helpers\FormHelper();

        // Register setting first
        $this->settings_helper->register();

        // Set page as current
        $this->settings_helper->setSettingsPage($this->slug);

        // Section: Disable pingbacks
        $this->settings_helper->addSettingsSection(
            'disable-pingback',
            __('Disable pingbacks', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    __('<strong>Pingbacks</strong> can be <a href="%s">misused for DDoS attacks</a>. Although the "Allow link notifications from other blogs" setting can be used to disable them, it does not affect configuration of existing posts.', 'bc-security'),
                    'http://blog.sucuri.net/2014/03/more-than-162000-wordpress-sites-used-for-distributed-denial-of-service-attack.html'
                ) . '</p>';
            }
        );
        $this->settings_helper->addSettingsField(
            Settings::DISABLE_PINGBACKS,
            __('Disable pingbacks', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );

        // Section: Disable XML-RPC methods that require authentication
        $this->settings_helper->addSettingsSection(
            'disable-xml-rpc',
            __('Disable XML-RPC methods that require authentication', 'bc-security'),
            function () {
                echo '<p>' . sprintf(
                    __('Disabling methods that require <strong>XML-RPC authentication</strong> helps to prevent <a href="%s">brute force attacks</a>.', 'bc-security'),
                    'https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html'
                ) . '</p>';
            }
        );
        $this->settings_helper->addSettingsField(
            Settings::DISABLE_XML_RPC,
            __('Disable XML-RPC methods', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );

        // Section: Disable REST API to anonymous users
        $this->settings_helper->addSettingsSection(
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
        $this->settings_helper->addSettingsField(
            Settings::DISABLE_REST_API,
            __('Disable REST API access', 'bc-security'),
            [$form_helper, 'renderCheckbox']
        );
    }
}
