<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Login;

use BlueChip\Security\Helpers\Utils;
use BlueChip\Security\Modules\Access\Bouncer;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\InternalBlocklist\BanReason;
use BlueChip\Security\Modules\InternalBlocklist\Manager as InternalBlocklistManager;
use BlueChip\Security\Modules\Loadable;
use WP_Error;
use WP_User;

/**
 * Gatekeeper keeps bots out of admin area.
 */
class Gatekeeper implements Initializable, Loadable
{
    /**
     * @param Settings $settings
     * @param string $remote_address Remote IP address.
     * @param Bookkeeper $bookkeeper
     * @param InternalBlocklistManager $ib_manager
     * @param Bouncer $bouncer
     */
    public function __construct(
        private Settings $settings,
        private string $remote_address,
        private Bookkeeper $bookkeeper,
        private InternalBlocklistManager $ib_manager,
        private Bouncer $bouncer
    ) {
    }


    /**
     * Load module.
     */
    public function load(): void
    {
        // Remove all WordPress authentication cookies if remote access to admin is blocked.
        if ($this->bouncer->isBlocked(Scope::ADMIN)) {
            $this->clearAuthCookie();
        }
    }


    /**
     * Initialize login hardening.
     */
    public function init(): void
    {
        add_filter('illegal_user_logins', $this->filterIllegalUserLogins(...), 10, 1);

        add_filter('authenticate', $this->lockIpIfUsernameOnBlacklist(...), 25, 2); // should run after default authentication filters

        if ($this->settings[Settings::GENERIC_LOGIN_ERROR_MESSAGE]) {
            add_filter('authenticate', $this->muteStandardErrorMessages(...), 100, 1); // 100 ~ should run last
            add_filter('shake_error_codes', $this->filterShakeErrorCodes(...), 10, 1);
        }

        add_action('wp_login_failed', $this->handleFailedLogin(...), 100, 1);

        // Check authentication cookies:
        add_action('auth_cookie_bad_username', $this->handleBadCookie(...), 10, 1);
        add_action('auth_cookie_bad_hash', $this->handleBadCookie(...), 10, 1);
    }


    /**
     * Filter the list of blacklisted usernames.
     *
     * @filter https://developer.wordpress.org/reference/hooks/illegal_user_logins/
     *
     * @param string[] $usernames
     *
     * @return string[]
     */
    private function filterIllegalUserLogins(array $usernames): array
    {
        return \array_merge($usernames, $this->settings->getUsernameBlacklist());
    }


    /**
     * Let generic `authentication_failed` error shake the login form.
     *
     * @filter https://developer.wordpress.org/reference/hooks/shake_error_codes/
     *
     * @param string[] $error_codes
     *
     * @return string[]
     */
    private function filterShakeErrorCodes(array $error_codes): array
    {
        $error_codes[] = 'authentication_failed';
        return $error_codes;
    }


    /**
     * Perform necessary actions when login via cookie fails due bad username or bad hash.
     *
     * @param array<string,string> $cookie_elements
     */
    private function handleBadCookie(array $cookie_elements): void
    {
        // Clear authentication cookies completely
        $this->clearAuthCookie();
        // Handle failed login for username stored in cookie
        $this->handleFailedLogin($cookie_elements['username']);
    }


    /**
     * Perform necessary actions when login attempt failed:
     * 1) Increase number of retries (if necessary).
     * 2) Reset valid value.
     * 3) Perform lockout if number of retries is above threshold.
     *
     * @action https://developer.wordpress.org/reference/hooks/wp_login_failed/
     *
     * @param string $username
     */
    private function handleFailedLogin(string $username): void
    {
        // Record failed login attempt, get total number of retries for IP
        $retries = $this->bookkeeper->recordFailedLoginAttempt($this->remote_address, $username);

        // Determine whether it is the lockout time:
        if ($retries % $this->settings[Settings::LONG_LOCKOUT_AFTER] === 0) {
            // Long lockout
            $this->lockOut($username, $this->settings->getLongLockoutDuration(), BanReason::LOGIN_LOCKOUT_LONG);
        } elseif ($retries % $this->settings[Settings::SHORT_LOCKOUT_AFTER] === 0) {
            // Short lockout
            $this->lockOut($username, $this->settings->getShortLockoutDuration(), BanReason::LOGIN_LOCKOUT_SHORT);
        }
    }


    /**
     * Lock IP out and die with 503 error if non-existing $username has been
     * used to log in and is present on username blacklist.
     *
     * Filter is called from wp_authenticate().
     *
     * @filter https://developer.wordpress.org/reference/hooks/authenticate/
     */
    private function lockIpIfUsernameOnBlacklist(WP_Error|WP_User|null $user, string $username): WP_Error|WP_User|null
    {
        // When a non-existing username (or email)...
        if (is_wp_error($user) && ($user->get_error_code() === 'invalid_username' || $user->get_error_code() === 'invalid_email')) {
            // ...is found on black list...
            if (\in_array($username, $this->settings->getUsernameBlacklist(), true)) {
                // ...lock IP out!
                $this->lockOut($username, $this->settings->getLongLockoutDuration(), BanReason::USERNAME_BLACKLIST);
            }
        }

        return $user;
    }


    /**
     * Return null instead of WP_Error when authentication fails because of
     * invalid username, email or password forcing WP to display generic error
     * message.
     *
     * @filter https://developer.wordpress.org/reference/hooks/authenticate/
     */
    private function muteStandardErrorMessages(WP_Error|WP_User|null $user): WP_Error|WP_User|null
    {
        if (is_wp_error($user)) {
            switch ($user->get_error_code()) {
                case 'invalid_username':
                case 'invalid_email':
                case 'incorrect_password':
                    return null;
            }
        }
        return $user;
    }


    /**
     * Clear all WordPress authentication cookies (also for current request).
     */
    private function clearAuthCookie(): void
    {
        wp_clear_auth_cookie();

        unset($_COOKIE[AUTH_COOKIE]);
        unset($_COOKIE[SECURE_AUTH_COOKIE]);
        unset($_COOKIE[LOGGED_IN_COOKIE]);
    }


    /**
     * Lock out remote IP address for $duration seconds
     *
     * @hook \BlueChip\Security\Modules\Login\Hooks::LOCKOUT_EVENT
     *
     * @param string $username Username that triggered the lockout
     * @param int $duration Duration (in secs) of lockout
     * @param BanReason $ban_reason Lockout reason
     */
    private function lockOut(string $username, int $duration, BanReason $ban_reason): void
    {
        // Lock IP address
        if ($this->ib_manager->lock($this->remote_address, $duration, Scope::ADMIN, $ban_reason)) {
            // Trigger lockout action
            do_action(Hooks::LOCKOUT_EVENT, $this->remote_address, $username, $duration, $ban_reason);
        }

        // Block access
        Utils::blockAccessTemporarily($this->remote_address);
    }
}
