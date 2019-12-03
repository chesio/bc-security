<?php

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Modules\Login;

class EventsMonitor implements \BlueChip\Security\Modules\Initializable
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

        if ($wp_query->is_404() && apply_filters(Hooks::LOG_404_EVENT, true, $wp->request)) {
            do_action(Action::EVENT, (new Events\Query404())->setRequestUri($wp->request));
        }
    }


    /**
     * Log when bad cookie is used for authentication.
     *
     * @param array $cookie_elements
     */
    public function logBadCookie(array $cookie_elements)
    {
        do_action(Action::EVENT, (new Events\AuthBadCookie())->setUsername($cookie_elements['username']));
    }


    /**
     * Log failed login.
     *
     * @param string $username
     */
    public function logFailedLogin(string $username)
    {
        do_action(Action::EVENT, (new Events\LoginFailure())->setUsername($username));
    }


    /**
     * Log successful login.
     *
     * @param string $username
     */
    public function logSuccessfulLogin(string $username)
    {
        do_action(Action::EVENT, (new Events\LoginSuccessful())->setUsername($username));
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
        do_action(Action::EVENT, (new Events\LoginLockout())->setDuration($duration)->setIpAddress($remote_address)->setUsername($username));
    }
}
