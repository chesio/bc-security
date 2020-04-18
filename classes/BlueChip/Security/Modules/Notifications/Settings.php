<?php

namespace BlueChip\Security\Modules\Notifications;

/**
 * Notifications settings
 */
class Settings extends \BlueChip\Security\Core\Settings
{
    /** bool: Notify when user with admin privileges logs in [Yes] */
    public const ADMIN_USER_LOGIN = 'admin_user_login';

    /** bool: Notify when known IP (IP for which there is a successful login in logs) is locked out [Yes] */
    public const KNOWN_IP_LOCKOUT = 'known_ip_lockout';

    /** bool: Notify when there is an update for WordPress available [Yes] */
    public const CORE_UPDATE_AVAILABLE = 'core_update_available';

    /** bool: Notify when there is a plugin update available [Yes] */
    public const PLUGIN_UPDATE_AVAILABLE = 'plugin_update_available';

    /** bool: Notify when there is a theme update available [Yes] */
    public const THEME_UPDATE_AVAILABLE = 'theme_update_available';

    /** bool: Notify when automatic checklist check triggers an alert [Yes] */
    public const CHECKLIST_ALERT = 'checklist_alert';

    /** bool: Notify when BC Security is deactivated [Yes] */
    public const PLUGIN_DEACTIVATED = 'plugin_deactivated';

    /** bool: Send notification to email address of site administrator [No] */
    public const NOTIFY_SITE_ADMIN = 'notify_site_admin';

    /** array: List of email addresses of any additional notifications [empty] */
    public const NOTIFICATION_RECIPIENTS = 'notification_recipients';

    /**
     * @var array Default values for all settings.
     */
    protected const DEFAULTS = [
        self::ADMIN_USER_LOGIN => true,
        self::KNOWN_IP_LOCKOUT => true,
        self::CORE_UPDATE_AVAILABLE => true,
        self::PLUGIN_UPDATE_AVAILABLE => true,
        self::THEME_UPDATE_AVAILABLE => true,
        self::CHECKLIST_ALERT => true,
        self::PLUGIN_DEACTIVATED => true,
        self::NOTIFY_SITE_ADMIN => false,
        self::NOTIFICATION_RECIPIENTS => [],
    ];

    /**
     * @var array Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::NOTIFICATION_RECIPIENTS => [self::class, 'sanitizeNotificationRecipient'],
    ];


    /**
     * Sanitize "notification recipients" setting. Must be list of emails.
     *
     * @param array|string $value
     * @return array
     */
    public static function sanitizeNotificationRecipient($value): array
    {
        return \array_filter(self::parseList($value), '\is_email');
    }
}
