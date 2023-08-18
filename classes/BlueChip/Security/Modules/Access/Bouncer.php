<?php

namespace BlueChip\Security\Modules\Access;

use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\InternalBlocklist\Manager as InternalBlocklistManager;
use BlueChip\Security\Modules\Loadable;
use BlueChip\Security\Helpers\Utils;
use BlueChip\Security\Modules\ExternalBlocklist\Manager as ExternalBlocklistManager;
use WP_Error;
use WP_User;

/**
 * Bouncer takes care of bouncing uninvited guests by:
 * 1) Blocking access to website when remote IP address cannot be determined.
 * 2) Blocking access to website when remote IP address is on external or internal blocklist.
 */
class Bouncer implements Initializable, Loadable
{
    /**
     * @param string $remote_address Remote IP address.
     * @param InternalBlocklistManager $ib_manager
     * @param ExternalBlocklistManager $eb_manager
     */
    public function __construct(
        private string $remote_address,
        private InternalBlocklistManager $ib_manager,
        private ExternalBlocklistManager $eb_manager
    ) {
    }


    /**
     * Load module.
     */
    public function load(): void
    {
        // As much as I hate to add callbacks to hooks that are already being executed,
        // I have to balance two requirements here:
        // 1) Run the access check as early as possible (I consider `init` hook too late).
        // 2) Allow myself and others to hook stuff (ie. events logger) in a clean way before access check executes.
        add_action('plugins_loaded', [$this, 'checkAccess'], 1, 0);
    }


    /**
     * Initialize module.
     */
    public function init(): void
    {
        add_filter('authenticate', [$this, 'checkLoginAttempt'], 1, 1); // Leave priority 0 for site maintainers.
    }


    /**
     * Should the request from current remote address be blocked for given access $access_scope?
     *
     * @param Scope $access_scope
     *
     * @return bool
     */
    public function isBlocked(Scope $access_scope): bool
    {
        // Check external blocklist.
        $source = $this->eb_manager->getBlocklist($access_scope)->getSource($this->remote_address);
        $eb_result = $source !== null;
        $ib_result = $this->ib_manager->isLocked($this->remote_address, $access_scope);

        if ($eb_result) {
            do_action(Hooks::EXTERNAL_BLOCKLIST_HIT_EVENT, $this->remote_address, $access_scope, $source);
        }

        if ($ib_result) {
            do_action(Hooks::INTERNAL_BLOCKLIST_HIT_EVENT, $this->remote_address, $access_scope);
        }

        return apply_filters(Hooks::IS_IP_ADDRESS_BLOCKED, $eb_result || $ib_result, $this->remote_address, $access_scope);
    }


    //// Hookers - public methods that should in fact be private

    /**
     * Check if access to website is allowed from given remote address.
     */
    public function checkAccess(): void
    {
        if ($this->isBlocked(Scope::WEBSITE)) {
            Utils::blockAccessTemporarily($this->remote_address);
        }
    }


    /**
     * Check if access to login is allowed from given remote address.
     */
    public function checkLoginAttempt(WP_Error|WP_User|null $user): WP_Error|WP_User|null
    {
        if ($this->isBlocked(Scope::ADMIN)) {
            Utils::blockAccessTemporarily($this->remote_address);
        }

        return $user;
    }
}
