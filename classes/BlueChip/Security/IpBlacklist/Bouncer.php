<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\IpBlacklist;

/**
 * Bouncer takes care of bouncing uninvited guests by:
 * 1) Blocking access to website, if remote IP address cannot be determined.
 * 2) Blocking access to website, if remote IP address is on website blacklist.
 */
class Bouncer implements \BlueChip\Security\Core\Module\Initializable, \BlueChip\Security\Core\Module\Loadable
{
    /** @var \BlueChip\Security\IpBlacklist\Manager */
    private $bl_manager;

    /** @var string Remote IP address */
    private $ip_address;


    /**
     * @param string $ip_address
     * @param Manager $bl_manager
     */
    public function __construct($ip_address, Manager $bl_manager)
    {
        $this->bl_manager = $bl_manager;
        $this->ip_address = $ip_address;
    }


    /**
     * Load module.
     */
    public function load()
    {
        // If IP address is invalid, die immediately.
        if (empty($this->ip_address)) {
            self::blockAccessTemporarily();
        }

        // Check, if access to website is allowed as early as possible
        // (do not use priority 0 - leave it to website maintainers)
        add_filter('plugins_loaded', [$this, 'checkAccess'], 1, 0);
    }


    /**
     * Initialize module.
     */
    public function init()
    {
        // Check, if access to login is allowed as early as possible, but
        // do not use priority 0 - leave it to website maintainers.
        add_filter('authenticate', [$this, 'checkLoginAttempt'], 1, 1);
    }


    /**
     * Terminate script execution via wp_die(), pass 503 as return code.
     *
     * @link https://httpstatusdogs.com/503-service-unavailable
     *
     * @param string $ip_address Remote IP address to include in error message [optional].
     */
    public static function blockAccessTemporarily($ip_address = '')
    {
        $error_msg = empty($ip_address)
            ? esc_html__('Access from your device has been temporarily disabled for security reasons.', 'bc-security')
            : sprintf(esc_html__('Access from your IP address %1$s has been temporarily disabled for security reasons.', 'bc-security'), sprintf('<em>%s</em>', $ip_address))
        ;
        //
        wp_die($error_msg, __('Service Temporarily Unavailable', 'bc-security'), 503);
    }


    //// Hookers - public methods that should in fact be private

    /**
     * Immediately wp_die(), if current IP has restricted access to website.
     */
    public function checkAccess()
    {
        if ($this->bl_manager->isLocked($this->ip_address, LockScope::WEBSITE)) {
            self::blockAccessTemporarily($this->ip_address);
        }
    }


    /**
     * Immediately wp_die(), if current IP has restricted access to login.
     *
     * @param WP_Error|WP_User $user
     * @return WP_Error|WP_User
     */
    public function checkLoginAttempt($user)
    {
        if ($this->bl_manager->isLocked($this->ip_address, LockScope::ADMIN)) {
            self::blockAccessTemporarily($this->ip_address);
        }

        return $user;
    }
}
