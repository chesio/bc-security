<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Notifications;

use BlueChip\Security\Core\Settings as CoreSettings;

/**
 * Notifications settings
 */
class Settings extends CoreSettings
{
    /**
     * @var string Notify when user with admin privileges logs in [bool:yes]
     */
    public const ADMIN_USER_LOGIN = 'admin_user_login';

    /**
     * @var string Notify when known IP (IP for which there is a successful login in logs) is locked out [bool:yes]
     */
    public const KNOWN_IP_LOCKOUT = 'known_ip_lockout';

    /**
     * @var string Notify when there is an update for WordPress available [bool:yes]
     */
    public const CORE_UPDATE_AVAILABLE = 'core_update_available';

    /**
     * @var string Notify when there is a plugin update available [bool:yes]
     */
    public const PLUGIN_UPDATE_AVAILABLE = 'plugin_update_available';

    /**
     * @var string Notify when there is a theme update available [bool:yes]
     */
    public const THEME_UPDATE_AVAILABLE = 'theme_update_available';

    /**
     * @var string Notify when automatic checklist check triggers an alert [bool:yes]
     */
    public const CHECKLIST_ALERT = 'checklist_alert';

    /**
     * @var string Notify when BC Security is deactivated [bool:yes]
     */
    public const PLUGIN_DEACTIVATED = 'plugin_deactivated';

    /**
     * @var string Send notification to email address of site administrator [bool:no]
     */
    public const NOTIFY_SITE_ADMIN = 'notify_site_admin';

    /**
     * @var string List of email addresses of any additional notifications [array:empty]
     */
    public const NOTIFICATION_RECIPIENTS = 'notification_recipients';

    /**
     * @var array<string,mixed> Default values for all settings.
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
     * @var array<string,callable> Custom sanitizers.
     */
    protected const SANITIZERS = [
        self::NOTIFICATION_RECIPIENTS => [self::class, 'sanitizeNotificationRecipient'],
    ];


    /**
     * Sanitize "notification recipients" setting. Must be list of emails.
     *
     * @param string|string[] $value
     *
     * @return string[]
     */
    public static function sanitizeNotificationRecipient($value): array
    {
        return \array_filter(self::parseList($value), '\is_email');
    }
}
