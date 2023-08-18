<?php

namespace BlueChip\Security\Modules\InternalBlocklist;

/**
 * List of different reasons for an IP address to find itself onto internal blocklist
 */
enum BanReason: int
{
    case LOGIN_LOCKOUT_SHORT = 1;
    case LOGIN_LOCKOUT_LONG = 2;
    case USERNAME_BLACKLIST = 3;
    case MANUALLY_BLOCKED = 4;
    case BAD_REQUEST_BAN = 5;
}
