<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Notifications;

/**
 * Hooks available in notifications module
 */
interface Hooks
{
    /**
     * Filter: whether to send one notification in case there are multiple plugin updates discovered at once?
     */
    public const ALL_PLUGIN_UPDATES_IN_ONE_NOTIFICATION = 'bc-security/filter:all-plugin-updates-in-one-notification';

    /**
     * Filter: whether to send one notification in case there are multiple theme updates discovered at once?
     */
    public const ALL_THEME_UPDATES_IN_ONE_NOTIFICATION = 'bc-security/filter:all-theme-updates-in-one-notification';
}
