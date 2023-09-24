<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Access;

/**
 * A different ways to restrict access to the website from a remote address.
 */
enum Scope: int
{
    /**
     * Not a real scope, just a safe value to use whenever scope is undefined.
     */
    case ANY = 0;

    /**
     * No access to admin (or login attempt) from IP address is allowed.
     */
    case ADMIN = 1;

    /**
     * No comments from IP address are allowed.
     */
    case COMMENTS = 2;

    /**
     * No access to website from IP address is allowed.
     */
    case WEBSITE = 3;

    /**
     * @return string Human-readable description for scope
     */
    public function describe(): string
    {
        return match ($this) {
            self::ANY => __('Do not block anything', 'bc-security'),
            self::ADMIN => __('Block access to login', 'bc-security'),
            self::COMMENTS => __('Block access to comments functionality', 'bc-security'),
            self::WEBSITE => __('Block access to entire website', 'bc-security'),
        };
    }

    /**
     * Get a list of all lock scopes with human readable description.
     *
     * @return array<int,string>
     */
    public static function explain(): array
    {
        $access_scopes = self::cases();

        return \array_combine(
            \array_map(fn (Scope $access_scope): int => $access_scope->value, $access_scopes),
            \array_map(fn (Scope $access_scope): string => $access_scope->describe(), $access_scopes),
        );
    }
}
