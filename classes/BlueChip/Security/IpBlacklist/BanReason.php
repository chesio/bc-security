<?php
/**
 * @package BC_Security
 */
namespace BlueChip\Security\IpBlacklist;

/**
 * Possible reasons behind being blacklisted
 */
interface BanReason
{
    const LOGIN_LOCKOUT_SHORT = 1;
    const LOGIN_LOCKOUT_LONG = 2;
    const USERNAME_BLACKLIST = 3;
    const MANUALLY_BLACKLISTED = 4;
}
