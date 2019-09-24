<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\IpBlacklist;

use BlueChip\Security\Helpers;

/**
 * Bouncer takes care of bouncing uninvited guests by:
 * 1) Blocking access to website, if remote IP address cannot be determined.
 * 2) Blocking access to website, if remote IP address is on website blacklist.
 */
class Bouncer implements \BlueChip\Security\Modules\Initializable, \BlueChip\Security\Modules\Loadable
{
    /**
     * @var \BlueChip\Security\Modules\IpBlacklist\Manager
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
    public function __construct(string $remote_address, Manager $bl_manager)
    {
        $this->bl_manager = $bl_manager;
        $this->remote_address = $remote_address;
    }


    /**
     * Load module.
     */
    public function load()
    {
        // In case of non-cli context, if remote IP address is invalid, die immediately.
        if (!Helpers\Is::cli() && empty($this->remote_address)) {
            self::blockAccessTemporarily();
        }

        // Check, if access to website is allowed.
        add_filter('plugins_loaded', [$this, 'checkAccess'], 1, 0); // Leave priority 0 for site maintainers.
    }


    /**
     * Initialize module.
     */
    public function init()
    {
        add_filter('authenticate', [$this, 'checkLoginAttempt'], 1, 1); // Leave priority 0 for site maintainers.
    }


    /**
     * Terminate script execution via wp_die(), pass 503 as return code.
     *
     * @link https://httpstatusdogs.com/503-service-unavailable
     *
     * @param string $ip_address Remote IP address to include in error message [optional].
     */
    public static function blockAccessTemporarily(string $ip_address = '')
    {
        $error_msg = empty($ip_address)
            ? esc_html__('Access from your device has been temporarily disabled for security reasons.', 'bc-security')
            : \sprintf(esc_html__('Access from your IP address %1$s has been temporarily disabled for security reasons.', 'bc-security'), \sprintf('<em>%s</em>', $ip_address))
        ;
        //
        wp_die($error_msg, __('Service Temporarily Unavailable', 'bc-security'), 503);
    }


    //// Hookers - public methods that should in fact be private

    /**
     * Block access to the website, if remote IP address is locked.
     */
    public function checkAccess()
    {
        if ($this->bl_manager->isLocked($this->remote_address, LockScope::WEBSITE)) {
            self::blockAccessTemporarily($this->remote_address);
        }
    }


    /**
     * Block access to the login, if remote IP address is locked.
     *
     * @param \WP_Error|\WP_User $user
     * @return \WP_Error|\WP_User
     */
    public function checkLoginAttempt($user)
    {
        if ($this->bl_manager->isLocked($this->remote_address, LockScope::ADMIN)) {
            self::blockAccessTemporarily($this->remote_address);
        }

        return $user;
    }
}
