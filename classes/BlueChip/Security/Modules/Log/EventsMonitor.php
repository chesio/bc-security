<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log;

use BlueChip\Security\Modules\Access\Hooks as AccessHooks;
use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\ExternalBlocklist\Source;
use BlueChip\Security\Modules\BadRequestsBanner\Hooks as BadRequestsBannerHooks;
use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\Loadable;
use BlueChip\Security\Modules\Login\Hooks as LoginHooks;
use BlueChip\Security\Modules\BadRequestsBanner\BanRule;
use WP;
use WP_Error;

class EventsMonitor implements Initializable, Loadable
{
    /**
     * @param string $remote_address Remote IP address.
     * @param string $server_address Server IP address.
     */
    public function __construct(private string $remote_address, private string $server_address)
    {
    }


    public function load(): void
    {
        // Depending on access scope, blocklist can be checked very early, so add these monitors early.
        add_action(AccessHooks::EXTERNAL_BLOCKLIST_HIT_EVENT, [$this, 'logExternalBlocklistHit'], 10, 3);
        add_action(AccessHooks::INTERNAL_BLOCKLIST_HIT_EVENT, [$this, 'logInternalBlocklistHit'], 10, 2);
    }


    public function init(): void
    {
        // Log the following WordPress events:
        // - bad authentication cookie
        add_action('auth_cookie_bad_username', [$this, 'logBadCookie'], 5, 1);
        add_action('auth_cookie_bad_hash', [$this, 'logBadCookie'], 5, 1);
        // - failed login
        add_action('wp_login_failed', [$this, 'logFailedLogin'], 5, 2);
        // - successful login
        add_action('wp_login', [$this, 'logSuccessfulLogin'], 5, 1);
        // - 404 query (only if request did not originate from the webserver itself)
        if ($this->remote_address !== $this->server_address) {
            add_action('wp', [$this, 'log404Queries'], 20, 1);
        }

        // Log the following BC Security events:
        // - lockout event
        add_action(LoginHooks::LOCKOUT_EVENT, [$this, 'logLockoutEvent'], 10, 3);
        // - bad request event
        add_action(BadRequestsBannerHooks::BAD_REQUEST_EVENT, [$this, 'logBadRequestEvent'], 10, 3);
    }


    /**
     * Log external blocklist hit.
     */
    public function logExternalBlocklistHit(string $remote_address, Scope $access_scope, Source $source): void
    {
        do_action(Action::EVENT, (new Events\BlocklistHit())->setIpAddress($remote_address)->setRequestType($access_scope)->setSource($source));
    }


    /**
     * Log internal blocklist hit.
     */
    public function logInternalBlocklistHit(string $remote_address, Scope $access_scope): void
    {
        do_action(Action::EVENT, (new Events\BlocklistHit())->setIpAddress($remote_address)->setRequestType($access_scope));
    }


    /**
     * Log 404 event (main queries that returned no results).
     *
     * Note: `parse_query` action cannot be used for 404 detection, because 404 state can be set as late as in WP::main().
     *
     * @see WP::main()
     */
    public function log404Queries(WP $wp): void
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
     * @param array<string,string> $cookie_elements
     */
    public function logBadCookie(array $cookie_elements): void
    {
        do_action(Action::EVENT, (new Events\AuthBadCookie())->setUsername($cookie_elements['username']));
    }


    /**
     * Log failed login.
     */
    public function logFailedLogin(string $username, WP_Error $error): void
    {
        do_action(Action::EVENT, (new Events\LoginFailure())->setUsername($username)->setError($error));
    }


    /**
     * Log successful login.
     */
    public function logSuccessfulLogin(string $username): void
    {
        do_action(Action::EVENT, (new Events\LoginSuccessful())->setUsername($username));
    }


    /**
     * Log lockout event.
     */
    public function logLockoutEvent(string $remote_address, string $username, int $duration): void
    {
        do_action(Action::EVENT, (new Events\LoginLockout())->setDuration($duration)->setIpAddress($remote_address)->setUsername($username));
    }


    /**
     * Log bad request event.
     */
    public function logBadRequestEvent(string $remote_address, string $request, BanRule $ban_rule): void
    {
        do_action(Action::EVENT, (new Events\BadRequestBan())->setBanRuleName($ban_rule->getName())->setIpAddress($remote_address)->setRequestUri($request));
    }
}
