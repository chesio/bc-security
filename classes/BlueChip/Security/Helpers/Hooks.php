<?php

namespace BlueChip\Security\Helpers;

/**
 * Hooks available within helper classes
 */
interface Hooks
{
    /**
     * Filter: allows to change return value of Is::admin() helper.
     *
     * @see \BlueChip\Security\Helpers\Is::admin()
     */
    public const IS_ADMIN = 'bc-security/filter:is-admin';


    /**
     * Filter: allows to change return value of Is::live() helper.
     *
     * @see \BlueChip\Security\Helpers\Is::live()
     */
    public const IS_LIVE = 'bc-security/filter:is-live';


    /**
     * Filter: allows to change plugin's changelog URL.
     *
     * @see \BlueChip\Security\Helpers\Plugin::getChangelogUrl()
     */
    public const PLUGIN_CHANGELOG_URL = 'bc-security/filter:plugin-changelog-url';
}
