<?php

namespace BlueChip\Security\Modules\InternalBlocklist;

/**
 * List of different reasons for an IP address to find itself onto internal blocklist
 */
interface BanReason
{
    public const LOGIN_LOCKOUT_SHORT = 1;
    public const LOGIN_LOCKOUT_LONG = 2;
    public const USERNAME_BLACKLIST = 3;
    public const MANUALLY_BLOCKED = 4;
    public const BAD_REQUEST_BAN = 5;
}
