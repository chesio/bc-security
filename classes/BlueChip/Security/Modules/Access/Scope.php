<?php

namespace BlueChip\Security\Modules\Access;

/**
 * A different ways to restrict access to the website from a remote address.
 */
abstract class Scope
{
    /**
     * Not a real scope, just a safe value to use whenever scope is undefined.
     */
    public const ANY = 0;

    /**
     * No access to admin (or login attempt) from IP address is allowed.
     */
    public const ADMIN = 1;

    /**
     * No comments from IP address are allowed.
     */
    public const COMMENTS = 2;

    /**
     * No access to website from IP address is allowed.
     */
    public const WEBSITE = 3;

    /**
     * Get a list of all lock scopes.
     *
     * @param bool $explain Return array with scope as key and explanation as value.
     * @return array<int,string>|int[] Array of known (valid) lock scopes.
     */
    public static function enlist(bool $explain = false): array
    {
        $list = [
            self::ANY => __('Do not block anything', 'bc-security'),
            self::ADMIN => __('Block access to login', 'bc-security'),
            self::COMMENTS => __('Block access to comments functionality', 'bc-security'),
            self::WEBSITE => __('Block access to entire website', 'bc-security'),
        ];

        return $explain ? $list : \array_keys($list);
    }
}
