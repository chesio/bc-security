<?php

namespace BlueChip\Security\Modules\Hardening;

use BlueChip\Security\Helpers\AdminNotices;
use BlueChip\Security\Helpers\HaveIBeenPwned;

/**
 * Make WordPress harder to break into.
 */
class Core implements \BlueChip\Security\Modules\Initializable
{
    /**
     * @var string
     */
    private const AUTHOR_SCAN_QUERY_VAR = 'author_scan';

    /**
     * @var string
     */
    private const PWNED_PASSWORD_META_KEY = 'bc-security/pwned-password';

    /**
     * @var bool
     */
    private $rest_api_supressed;

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
        $this->rest_api_supressed = false;
    }


    /**
     * Initialize WP hardening.
     */
    public function init()
    {
        if ($this->settings[Settings::DISABLE_PINGBACKS]) {
            // Disable pingbacks.
            add_filter('xmlrpc_methods', [$this, 'disablePingbacks'], 10, 1);
        }
        if ($this->settings[Settings::DISABLE_XML_RPC]) {
            // Disable all XML-RPC methods requiring authentication.
            add_filter('xmlrpc_enabled', '__return_false', 10, 0);
        }
        if ($this->settings[Settings::DISABLE_APPLICATION_PASSWORDS]) {
            // Disable application passwords.
            add_filter('wp_is_application_passwords_available', '__return_false', 10, 0);
        }
        if ($this->settings[Settings::DISABLE_USERNAMES_DISCOVERY]) {
            // Alter REST API responses.
            add_filter('oembed_response_data', [$this, 'filterAuthorInOembed'], 100, 1);
            add_filter('rest_request_before_callbacks', [$this, 'filterJsonAPIAuthor'], 100, 3);
            add_filter('rest_post_dispatch', [$this, 'adjustJsonAPIHeaders'], 100, 1);
            if (!is_admin()) {
                // Prevent usernames enumeration.
                add_filter('request', [$this, 'filterAuthorQuery'], 5, 1);
                add_action('parse_request', [$this, 'stopAuthorScan'], 10, 1);
            }
        }
        if ($this->settings[Settings::CHECK_PASSWORDS]) {
            // Check user password on successful login.
            add_action('wp_login', [$this, 'checkUserPassword'], 10, 2);
            // Display warning notice, if pwned password has been detected for current user.
            add_action('current_screen', [$this, 'displayPasswordPwnedNotice'], 10, 1);
        }
        if ($this->settings[Settings::VALIDATE_PASSWORDS]) {
            // Validate password on user creation or profile update.
            add_action('user_profile_update_errors', [$this, 'validatePasswordUpdate'], 10, 3);
            // Validate password on password reset.
            add_action('validate_password_reset', [$this, 'validatePasswordReset'], 10, 2);
        }
    }


    /**
     * Remove pingback.ping from allowed/supported XML-RPC methods.
     *
     * @filter https://developer.wordpress.org/reference/hooks/xmlrpc_methods/
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
     * Remove author's name and URL from oEmbed data.
     *
     * @filter https://developer.wordpress.org/reference/hooks/oembed_response_data/
     *
     * @param array $data
     * @return array
     */
    public function filterAuthorInOembed(array $data): array
    {
        if (isset($data['author_name'])) {
            unset($data['author_name']);
        }
        if (isset($data['author_url'])) {
            unset($data['author_url']);
        }
        return $data;
    }


    /**
     * @filter https://developer.wordpress.org/reference/hooks/rest_request_before_callbacks/
     *
     * @param \WP_HTTP_Response|\WP_Error $response
     * @param array $handler
     * @param \WP_REST_Request $request
     * @return \WP_HTTP_Response|\WP_Error
     */
    public function filterJsonAPIAuthor($response, array $handler, \WP_REST_Request $request)
    {
        $route = $request->get_route();

        if (!current_user_can('list_users')) {
            // I <3 PHP 7!
            $url_base = (
                new class extends \WP_REST_Users_Controller {
                    public function getUrlBase(): string
                    {
                        return \rtrim($this->namespace . '/' . $this->rest_base, '/');
                    }
                }
            )->getUrlBase();

            if (\preg_match('#' . \preg_quote($url_base, '#') . '/*$#i', $route)) {
                $this->rest_api_supressed = true;
                return rest_ensure_response(new \WP_Error(
                    'rest_user_cannot_view',
                    __('Sorry, you are not allowed to list users.'), // WP core message
                    ['status' => rest_authorization_required_code()]
                ));
            }

            $matches = [];
            if (\preg_match('#' . \preg_quote($url_base, '#') . '/+(\d+)/*$#i', $route, $matches)) {
                if (get_current_user_id() !== (int) $matches[1]) {
                    $this->rest_api_supressed = true;
                    return rest_ensure_response(new \WP_Error(
                        'rest_user_invalid_id',
                        __('Invalid user ID.'), // WP core message.
                        ['status' => 404]
                    ));
                }
            }
        }

        return $response;
    }


    /**
     * @filter https://developer.wordpress.org/reference/hooks/rest_post_dispatch/
     *
     * @param \WP_HTTP_Response $response
     * @return \WP_HTTP_Response
     */
    public function adjustJsonAPIHeaders(\WP_HTTP_Response $response): \WP_HTTP_Response
    {
        if ($this->rest_api_supressed) {
            $response->header('Allow', 'GET');
        }

        return $response;
    }


    /**
     * @filter https://developer.wordpress.org/reference/hooks/request/
     *
     * @param array $query_vars
     * @return array
     */
    public function filterAuthorQuery(array $query_vars): array
    {
        if (self::smellsLikeAuthorScan($query_vars) && (self::smellsLikeAuthorScan($_GET) || self::smellsLikeAuthorScan($_POST))) {
            // I smell author scanning!
            $query_vars[self::AUTHOR_SCAN_QUERY_VAR] = true;
        }

        return $query_vars;
    }


    /**
     * Check whether given query variables contain author scan data.
     *
     * @link https://hackertarget.com/wordpress-user-enumeration/
     *
     * @param array $query_vars
     * @return bool True, if `author` key is present and its value is either an array or can be seen as numeric.
     */
    protected static function smellsLikeAuthorScan(array $query_vars): bool
    {
        return !empty($query_vars['author']) && (\is_array($query_vars['author']) || \is_numeric(\preg_replace('/[^0-9]/', '', $query_vars['author'])));
    }


    /**
     * Force "404 Not Found" response if query is marked as "author scan". If 404 template file exists, it is output.
     *
     * @param \WP $wp
     */
    public function stopAuthorScan(\WP $wp)
    {
        if ($wp->query_vars[self::AUTHOR_SCAN_QUERY_VAR] ?? false) {
            status_header(404);
            nocache_headers();

            if (!empty($template = get_404_template()) && \file_exists($template)) {
                include $template;
            }

            exit;
        }
    }


    /**
     * Check user password against Pwned Passwords database after successful login.
     *
     * @action https://developer.wordpress.org/reference/hooks/wp_login/
     *
     * @param string $username
     * @param \WP_User $user
     */
    public function checkUserPassword(string $username, \WP_User $user)
    {
        if (empty($password = \filter_input(INPUT_POST, 'pwd'))) {
            // Non-interactive sign on (probably).
            return;
        }

        if (HaveIBeenPwned::hasPasswordBeenPwned($password)) {
            // Mark user's password as pwned. Use actual hash ($user->user_pass) as a checksum.
            update_user_meta($user->ID, self::PWNED_PASSWORD_META_KEY, $user->user_pass);
        } else {
            // Clean up any out-dated data. Sadly, there is no useful hook related to password update.
            delete_user_meta($user->ID, self::PWNED_PASSWORD_META_KEY);
        }
    }


    /**
     * Display password pwned notice, if user's password is marked as pwned.
     *
     * @action https://developer.wordpress.org/reference/hooks/current_screen/
     *
     * @param \WP_Screen $screen
     */
    public function displayPasswordPwnedNotice(\WP_Screen $screen)
    {
        $user = wp_get_current_user();

        if (empty($pwned_password_hash = get_user_meta($user->ID, self::PWNED_PASSWORD_META_KEY, true))) {
            // User's password not marked as pwned.
            return;
        }

        if ($pwned_password_hash !== $user->user_pass) {
            // User's password marked as pwned, but actual password differ - probably has been changed since.
            return;
        }

        if (apply_filters(Hooks::SHOW_PWNED_PASSWORD_WARNING, true, $screen, $user)) {
            // Show the warning for current user on current screen.
            $notice = \sprintf(
                /* translators: 1: link to Pwned Passwords homepage, 2: link to profile editation page */
                esc_html__('Your password is present in a %1$s previously exposed in data breaches. Please, consider %2$s.', 'bc-security'),
                '<a href="' . HaveIBeenPwned::PWNEDPASSWORDS_HOME_URL . '" rel="noreferrer">' . esc_html__('large database of passwords', 'bc-security') . '</a>',
                '<a href="' . get_edit_profile_url($user->ID) . '">' . esc_html__('changing your password', 'bc-security') . '</a>'
            );

            AdminNotices::add($notice, AdminNotices::WARNING, false, false);
        }
    }


    /**
     * @action https://developer.wordpress.org/reference/hooks/user_profile_update_errors/
     *
     * @param \WP_Error $errors WP_Error object (passed by reference).
     * @param bool $update Whether this is a user update.
     * @param object $user User object (passed by reference).
     */
    public function validatePasswordUpdate(\WP_Error &$errors, bool $update, object &$user)
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
    public function validatePasswordReset(\WP_Error $errors, object $user)
    {
        if ($errors->get_error_code()) {
            // There is an error reported already, skip the check.
            return;
        }

        if (empty($password = \filter_input(INPUT_POST, 'pass1'))) {
            // Do not check empty password.
            return;
        }

        self::checkIfPasswordHasBeenPwned($password, $errors);
    }


    /**
     * Check, whether $password has been pwned and if so, add error message to $errors.
     *
     * @param string $password
     * @param \WP_Error $errors WP_Error object (passed by reference).
     */
    protected static function checkIfPasswordHasBeenPwned(string $password, \WP_Error &$errors)
    {
        if (HaveIBeenPwned::hasPasswordBeenPwned($password)) {
            $message = \sprintf(
                /* translators: 1: Error label, 2: link to Pwned Passwords homepage */
                esc_html__('%1$s: Provided password is present in a %2$s previously exposed in data breaches. Please, pick a different one.', 'bc-security'),
                '<strong>' . esc_html__('ERROR', 'bc-security') . '</strong>',
                '<a href="' . HaveIBeenPwned::PWNEDPASSWORDS_HOME_URL . '" rel="noreferrer">' . esc_html__('large database of passwords', 'bc-security') . '</a>'
            );
            $errors->add('password_has_been_pwned', $message);
        }
    }
}
