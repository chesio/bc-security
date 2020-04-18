<?php

namespace BlueChip\Security\Modules\IpBlacklist;

/**
 * Possible reasons behind being blacklisted
 */
interface BanReason
{
    public const LOGIN_LOCKOUT_SHORT = 1;
    public const LOGIN_LOCKOUT_LONG = 2;
    public const USERNAME_BLACKLIST = 3;
    public const MANUALLY_BLACKLISTED = 4;
}
