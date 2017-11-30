<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Notifications;

/**
 * Notifications settings
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** bool: Notify when user with admin privileges logs in [Yes] */
    const ADMIN_USER_LOGIN = 'admin_user_login';

    /** bool: Notify when known IP (IP for which there is a successful login in logs) is locked out [Yes] */
    const KNOWN_IP_LOCKOUT = 'known_ip_lockout';

    /** bool: Notify when there is an update for WordPress available [Yes] */
    const CORE_UPDATE_AVAILABLE = 'core_update_available';

    /** bool: Notify when there is a plugin update available [Yes] */
    const PLUGIN_UPDATE_AVAILABLE = 'plugin_update_available';

    /** bool: Notify when there is a theme update available [Yes] */
    const THEME_UPDATE_AVAILABLE = 'theme_update_available';

    /** bool: Notify when there is any error during core checksums verification [Yes] */
    const CORE_CHECKSUMS_VERIFICATION_ERROR = 'core_checksums_verification_error';

    /** bool: Notify when there is any error during plugin checksums verification [Yes] */
    const PLUGIN_CHECKSUMS_VERIFICATION_ERROR = 'plugin_checksums_verification_error';

    /** bool: Notify when BC Security is deactivated [Yes] */
    const PLUGIN_DEACTIVATED = 'plugin_deactivated';

    /** bool: Send notification to email address of site administrator [No] */
    const NOTIFY_SITE_ADMIN = 'notify_site_admin';

    /** array: List of email addresses of any additional notifications [empty] */
    const NOTIFICATION_RECIPIENTS = 'notification_recipients';


    /**
     * Sanitize settings array: only return known keys, provide default values for missing keys.
     *
     * @param array $s
     * @return array
     */
    public function sanitize(array $s)
    {
        return [
            self::ADMIN_USER_LOGIN
                => isset($s[self::ADMIN_USER_LOGIN]) ? boolval($s[self::ADMIN_USER_LOGIN]) : true,
            self::KNOWN_IP_LOCKOUT
                => isset($s[self::KNOWN_IP_LOCKOUT]) ? boolval($s[self::KNOWN_IP_LOCKOUT]) : true,
            self::CORE_UPDATE_AVAILABLE
                => isset($s[self::CORE_UPDATE_AVAILABLE]) ? boolval($s[self::CORE_UPDATE_AVAILABLE]) : true,
            self::PLUGIN_UPDATE_AVAILABLE
                => isset($s[self::PLUGIN_UPDATE_AVAILABLE]) ? boolval($s[self::PLUGIN_UPDATE_AVAILABLE]) : true,
            self::THEME_UPDATE_AVAILABLE
                => isset($s[self::THEME_UPDATE_AVAILABLE]) ? boolval($s[self::THEME_UPDATE_AVAILABLE]) : true,
            self::CORE_CHECKSUMS_VERIFICATION_ERROR
                => isset($s[self::CORE_CHECKSUMS_VERIFICATION_ERROR]) ? boolval($s[self::CORE_CHECKSUMS_VERIFICATION_ERROR]) : true,
            self::PLUGIN_CHECKSUMS_VERIFICATION_ERROR
                => isset($s[self::PLUGIN_CHECKSUMS_VERIFICATION_ERROR]) ? boolval($s[self::PLUGIN_CHECKSUMS_VERIFICATION_ERROR]) : true,
            self::PLUGIN_DEACTIVATED
                => isset($s[self::PLUGIN_DEACTIVATED]) ? boolval($s[self::PLUGIN_DEACTIVATED]) : true,
            self::NOTIFY_SITE_ADMIN
                => isset($s[self::NOTIFY_SITE_ADMIN]) ? boolval($s[self::NOTIFY_SITE_ADMIN]) : false,
            self::NOTIFICATION_RECIPIENTS
                => isset($s[self::NOTIFICATION_RECIPIENTS]) ? array_filter($this->parseList($s[self::NOTIFICATION_RECIPIENTS]), '\is_email') : [],
        ];
    }
}
