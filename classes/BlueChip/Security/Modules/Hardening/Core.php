<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Hardening;

use BlueChip\Security\Helpers\HaveIBeenPwned;

/**
 * Make WordPress harder to break into.
 */
class Core implements \BlueChip\Security\Modules\Initializable
{
    /**
     * @var \BlueChip\Security\Modules\Hardening\Settings
     */
    private $settings;


    /**
     * @param \BlueChip\Security\Modules\Hardening\Settings $settings
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
            add_filter('xmlrpc_methods', [$this, 'disablePingbacks'], 10, 1);
        }
        if ($this->settings[Settings::DISABLE_XML_RPC]) {
            // Disable all XML-RPC methods requiring authentication
            add_filter('xmlrpc_enabled', '__return_false', 10, 0);
        }
        if ($this->settings[Settings::DISABLE_REST_API]) {
            // Disable REST API methods to anonymous users
            add_filter('rest_authentication_errors', [$this, 'requireAuthForRestAccess'], 10, 1);
        }
        if ($this->settings[Settings::VALIDATE_PASSWORDS]) {
            // Validate password on user creation or profile update:
            add_action('user_profile_update_errors', [$this, 'validatePasswordUpdate'], 10, 3);
            // Validate password on password reset:
            add_action('validate_password_reset', [$this, 'validatePasswordReset'], 10, 2);
        }
    }


    /**
     * Remove pingback.ping from allowed/supported XML-RPC methods.
     *
     * @param array $methods
     * @return array
     */
    public function disablePingbacks(array $methods): array
    {
        unset($methods['pingback.ping']);
        return $methods;
    }


    /**
     * Return an authentication error if a user who is not logged in tries to query the REST API.
     *
     * @param mixed $access
     * @return WP_Error
     */
    public function requireAuthForRestAccess($access)
    {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_cannot_access',
                __('Only authenticated users can access the REST API.', 'bc-security'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return $access;
    }


    /**
     * @param \WP_Error $errors WP_Error object (passed by reference).
     * @param bool $update Whether this is a user update.
     * @param stdClass $user User object (passed by reference).
     */
    public function validatePasswordUpdate(\WP_Error &$errors, bool $update, &$user)
    {
        if ($errors->get_error_code()) {
            // There is an error reported already, skip the check.
            return;
        }

        if (!isset($user->user_pass)) {
            // No password provided (= no change of password requested).
            return;
        }

        self::checkIfPasswordHasBeenPwned($user->user_pass, $errors);
    }


    /**
     * Check reset password against Have I Been Pwned database.
     *
     * @action https://developer.wordpress.org/reference/hooks/validate_password_reset/
     *
     * @param \WP_Error $errors
     * @param \WP_User|\WP_Error $user WP_User object if the login and reset key match. WP_Error object otherwise.
     */
    public function validatePasswordReset(\WP_Error $errors, $user)
    {
        if ($errors->get_error_code()) {
            // There is an error reported already, skip the check.
            return;
        }

        if (empty($password = filter_input(INPUT_POST, 'pass1'))) {
            // Do not check empty password.
            return;
        }

        self::checkIfPasswordHasBeenPwned($password, $errors);
    }


    /**
     * Check, whether $password has been pwned and if so, add error message to $errors.
     *
     * @action https://developer.wordpress.org/reference/hooks/user_profile_update_errors/
     *
     * @param string $password
     * @param \WP_Error $errors WP_Error object (passed by reference).
     */
    protected static function checkIfPasswordHasBeenPwned(string $password, \WP_Error &$errors)
    {
        if (HaveIBeenPwned::hasPasswordBeenPwned($password)) {
            $errors->add('password_has_been_pwned', __('<strong>ERROR</strong>: Provided password is present in large password list from past data breaches on other websites. Please, pick a different one.', 'bc-security'));
        }
    }
}
