<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Events;

use BlueChip\Security\Modules\Checksums;
use BlueChip\Security\Modules\Log;
use BlueChip\Security\Modules\Login;

class Monitor implements \BlueChip\Security\Modules\Initializable
{
    /**
     * @var string Remote IP address
     */
    private $remote_address;

    /**
     * @var string Server IP address
     */
    private $server_address;


    /**
     * @param string $remote_address Remote IP address.
     * @param string $server_address Server IP address.
     */
    public function __construct(string $remote_address, string $server_address)
    {
        $this->remote_address = $remote_address;
        $this->server_address = $server_address;
    }


    public function init()
    {
        // Log the following WordPress events:
        // - bad authentication cookie
        add_action('auth_cookie_bad_username', [$this, 'logBadCookie'], 5, 1);
        add_action('auth_cookie_bad_hash', [$this, 'logBadCookie'], 5, 1);
        // - failed login
        add_action('wp_login_failed', [$this, 'logFailedLogin'], 5, 1);
        // - successful login
        add_action('wp_login', [$this, 'logSuccessfulLogin'], 5, 1);
        // - 404 query (only if request did not originate from the webserver itself)
        if ($this->remote_address !== $this->server_address) {
            add_action('wp', [$this, 'log404Queries'], 20, 1);
        }

        // Log the following BC Security events:
        // - lockout event
        add_action(Login\Hooks::LOCKOUT_EVENT, [$this, 'logLockoutEvent'], 10, 3);
        // - checksum verification alerts
        add_action(Checksums\Hooks::CORE_CHECKSUMS_VERIFICATION_ALERT, [$this, 'logCoreChecksumsVerificationAlert'], 10, 2);
        add_action(Checksums\Hooks::PLUGIN_CHECKSUMS_VERIFICATION_ALERT, [$this, 'logPluginChecksumsVerificationAlert'], 10, 1);
    }


    /**
     * Log 404 event (main queries that returned no results).
     *
     * Note: `parse_query` action cannot be used for 404 detection, because 404 state can be set as late as in WP::main().
     *
     * @see WP::main()
     *
     * @param \WP $wp
     */
    public function log404Queries(\WP $wp)
    {
        /** @var \WP_Query $wp_query */
        global $wp_query;

        if ($wp_query->is_404()) {
            do_action(Log\Action::EVENT, Log\Event::QUERY_404, ['request' => $wp->request]);
        }
    }


    /**
     * Log when bad cookie is used for authentication.
     *
     * @param array $cookie_elements
     */
    public function logBadCookie(array $cookie_elements)
    {
        do_action(Log\Action::EVENT, Log\Event::AUTH_BAD_COOKIE, ['username' => $cookie_elements['username']]);
    }


    /**
     * Log failed login.
     *
     * @param string $username
     */
    public function logFailedLogin(string $username)
    {
        do_action(Log\Action::EVENT, Log\Event::LOGIN_FAILURE, ['username' => $username]);
    }


    /**
     * Log successful login.
     *
     * @param string $username
     */
    public function logSuccessfulLogin(string $username)
    {
        do_action(Log\Action::EVENT, Log\Event::LOGIN_SUCCESSFUL, ['username' => $username]);
    }


    /**
     * Log lockout event.
     *
     * @param string $remote_address
     * @param string $username
     * @param int $duration
     */
    public function logLockoutEvent(string $remote_address, string $username, int $duration)
    {
        do_action(Log\Action::EVENT, Log\Event::LOGIN_LOCKOUT, ['ip_address' => $remote_address, 'duration' => $duration, 'username' => $username]);
    }


    /**
     * Log checksums verification alert for core files.
     *
     * @param array $modified_files Files for which official checksums do not match.
     * @param array $unknown_files Files that are present on file system but not in official checksums.
     */
    public function logCoreChecksumsVerificationAlert(array $modified_files, array $unknown_files)
    {
        do_action(Log\Action::EVENT, Log\Event::CHECKSUMS_VERIFICATION_ALERT, ['codebase' => __('WordPress core', 'bc-security'), 'modified_files' => $modified_files, 'unknown_files' => $unknown_files]);
    }


    /**
     * Log checksums verification alert for plugin files.
     *
     * @param array $plugins Plugins for which checksums verification triggered an alert.
     */
    public function logPluginChecksumsVerificationAlert(array $plugins)
    {
        foreach ($plugins as $plugin_data) {
            do_action(Log\Action::EVENT, Log\Event::CHECKSUMS_VERIFICATION_ALERT, ['codebase' => sprintf(__('"%s" plugin'), $plugin_data['Name']), 'modified_files' => $plugin_data['ModifiedFiles'], 'unknown_files' => $plugin_data['UnknownFiles']]);
        }
    }
}
