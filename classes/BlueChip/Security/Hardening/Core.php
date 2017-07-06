<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Hardening;

/**
 * Make WordPress harder to break into.
 */
class Core implements \BlueChip\Security\Core\Module\Initializable
{
    /**
     * @var \BlueChip\Security\Hardening\Settings
     */
    private $settings;


	/**
	 * @param \BlueChip\Security\Hardening\Settings $settings
	 */
	public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }


    /**
	 * Initialize WP hardening.
     */
    public function init()
    {
		if ($this->settings[Settings::DISABLE_PINGBACKS]) {
			// Disable pingbacks
			add_filter('xmlrpc_methods', [$this, 'disablePingbacks']);
		}
		if ($this->settings[Settings::DISABLE_XML_RPC]) {
			// Disable all XML-RPC methods requiring authentication
			add_filter('xmlrpc_enabled', '__return_false');
		}
        if ($this->settings[Settings::DISABLE_REST_API]) {
            // Disable REST API methods to anonymous users
            add_filter('rest_authentication_errors', [$this, 'requireAuthForRestAccess']);
        }
	}


	/**
	 * Remove pingback.ping from allowed/supported XML-RPC methods.
	 * @param array $methods
	 * @return array
	 */
	public function disablePingbacks($methods)
    {
		unset($methods['pingback.ping']);
		return $methods;
	}


    /**
     * Return an authentication error if a user who is not logged in tries to
     * query the REST API.
     * @param mixed $access
     * @return WP_Error
     */
    public function requireAuthForRestAccess($access)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_cannot_access', __('Only authenticated users can access the REST API.', 'bc-security'), ['status' => rest_authorization_required_code()]);
        }

        return $access;
    }
}
