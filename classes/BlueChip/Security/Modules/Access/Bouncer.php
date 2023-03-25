<?php

namespace BlueChip\Security\Modules\Access;

use BlueChip\Security\Modules\IpBlacklist\Manager as IpBlacklistManager;
use BlueChip\Security\Helpers\Utils;

/**
 * Bouncer takes care of bouncing uninvited guests by:
 * 1) Blocking access to website when remote IP address cannot be determined.
 * 2) Blocking access to website when remote IP address is on website blacklist.
 */
class Bouncer implements \BlueChip\Security\Modules\Initializable, \BlueChip\Security\Modules\Loadable
{
    /**
     * @var IpBlacklistManager
     */
    private $bl_manager;

    /**
     * @var string Remote IP address
     */
    private $remote_address;


    /**
     * @param string $remote_address Remote IP address.
     * @param Manager $bl_manager
     */
    public function __construct(string $remote_address, IpBlacklistManager $bl_manager)
    {
        $this->bl_manager = $bl_manager;
        $this->remote_address = $remote_address;
    }


    /**
     * Load module.
     */
    public function load(): void
    {
        // Check if access to website is allowed from given remote address.
        if ($this->bl_manager->isLocked($this->remote_address, Scope::WEBSITE)) {
            Utils::blockAccessTemporarily($this->remote_address);
        }
    }


    /**
     * Initialize module.
     */
    public function init(): void
    {
        add_filter('authenticate', [$this, 'checkLoginAttempt'], 1, 1); // Leave priority 0 for site maintainers.
    }


    //// Hookers - public methods that should in fact be private

    /**
     * Block access to the login when remote IP address is locked.
     *
     * @param \WP_Error|\WP_User $user
     *
     * @return \WP_Error|\WP_User
     */
    public function checkLoginAttempt($user)
    {
        if ($this->bl_manager->isLocked($this->remote_address, Scope::ADMIN)) {
            Utils::blockAccessTemporarily($this->remote_address);
        }

        return $user;
    }
}
