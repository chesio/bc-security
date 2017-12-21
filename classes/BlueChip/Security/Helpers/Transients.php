<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Helpers;

/**
 * Slightly more flexible API for transients.
 */
abstract class Transients
{
    /**
     * @var string Prefix common to all transients set by plugin.
     */
    const NAME_PREFIX = 'bc-security_';

    /**
     * Delete transient.
     *
     * @param string[] $key
     * @return bool
     */
    public static function deleteFromSite(string ...$key): bool
    {
        return delete_site_transient(self::name($key));
    }

    /**
     * Get transient.
     *
     * @param string[] $key
     * @return mixed
     */
    public static function getForSite(string ...$key)
    {
        return get_site_transient(self::name($key));
    }

    /**
     * Set transient.
     *
     * @param mixed $value
     * @param mixed[] $args
     * @return bool
     */
    public static function setForSite($value, ...$args): bool
    {
        // If the first variable argument is int, take it as expiration value.
        $expiration = is_int($args[0]) ? array_shift($args) : 0;

        return set_site_transient(self::name($args), $value, $expiration);
    }

    /**
     * Create transient name from $key.
     *
     * @param string[] $key
     * @return string
     */
    private static function name(array $key): string
    {
        return self::NAME_PREFIX . md5(implode(':', $key));
    }
}
