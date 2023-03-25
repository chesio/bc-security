<?php

namespace BlueChip\Security\Modules\Access;

use BlueChip\Security\Modules\Initializable;
use BlueChip\Security\Modules\IpBlacklist\Manager as IpBlacklistManager;
use BlueChip\Security\Modules\Loadable;
use BlueChip\Security\Helpers\Utils;
use BlueChip\Security\Modules\Hardening\ExternalBlocklist\Manager as ExternalBlocklistManager;

/**
 * Bouncer takes care of bouncing uninvited guests by:
 * 1) Blocking access to website when remote IP address cannot be determined.
 * 2) Blocking access to website when remote IP address is on external or internal blocklist.
 */
class Bouncer implements Initializable, Loadable
{
    /**
     * @var IpBlacklistManager
     */
    private $bl_manager;

    /**
     * @var ExternalBlocklistManager
     */
    private $eb_manager;

    /**
     * @var string Remote IP address
     */
    private $remote_address;


    /**
     * @param string $remote_address Remote IP address.
     * @param IpBlacklistManager $bl_manager
     * @param ExternalBlocklistManager $eb_manager
     */
    public function __construct(string $remote_address, IpBlacklistManager $bl_manager, ExternalBlocklistManager $eb_manager)
    {
        $this->bl_manager = $bl_manager;
        $this->eb_manager = $eb_manager;
        $this->remote_address = $remote_address;
    }


    /**
     * Load module.
     */
    public function load(): void
    {
        add_action('plugins_loaded', [$this, 'checkAccess'], 1, 0); // Leave priority 0 for site maintainers.
    }


    /**
     * Initialize module.
     */
    public function init(): void
    {
        add_filter('authenticate', [$this, 'checkLoginAttempt'], 1, 1); // Leave priority 0 for site maintainers.
    }


    /**
     * Should the request from current remote address be blocked for given access $scope?
     *
     * @param int $scope
     *
     * @return bool
     */
    public function isBlocked(int $scope): bool
    {
        $result = $this->eb_manager->isBlocked($this->remote_address, $scope) || $this->bl_manager->isLocked($this->remote_address, $scope);

        return apply_filters(Hooks::IS_IP_ADDRESS_BLOCKED, $result, $this->remote_address, $scope);
    }


    //// Hookers - public methods that should in fact be private

    /**
     * Check if access to website is allowed from given remote address.
     */
    public function checkAccess()
    {
        if ($this->isBlocked(Scope::WEBSITE)) {
            Utils::blockAccessTemporarily($this->remote_address);
        }
    }


    /**
     * Check if access to login is allowed from given remote address.
     *
     * @param \WP_Error|\WP_User $user
     *
     * @return \WP_Error|\WP_User
     */
    public function checkLoginAttempt($user)
    {
        if ($this->isBlocked(Scope::ADMIN)) {
            Utils::blockAccessTemporarily($this->remote_address);
        }

        return $user;
    }
}
