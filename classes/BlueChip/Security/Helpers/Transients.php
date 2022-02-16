<?php

namespace BlueChip\Security\Helpers;

/**
 * Slightly more flexible API for transients.
 */
abstract class Transients
{
    /**
     * @var string Prefix common to all transients set by plugin.
     */
    private const NAME_PREFIX = 'bc-security_';


    /**
     * Delete transient.
     *
     * @param string ...$key
     *
     * @return bool
     */
    public static function deleteFromSite(string ...$key): bool
    {
        return delete_site_transient(self::name($key));
    }


    /**
     * Remove all stored transients from database. Entire object cache is flushed as well, so use with caution.
     *
     * @link https://css-tricks.com/the-deal-with-wordpress-transients/
     *
     * @param \wpdb $wpdb WordPress database access abstraction object
     */
    public static function flush(\wpdb $wpdb): void
    {
        $table_name = is_multisite() ? $wpdb->sitemeta : $wpdb->options;

        // First, delete all transients from database...
        $wpdb->query(
            \sprintf(
                "DELETE FROM {$table_name} WHERE (option_name LIKE '%s' OR option_name LIKE '%s')",
                '_site_transient_' . self::NAME_PREFIX . '%',
                '_site_transient_timeout_' . self::NAME_PREFIX . '%'
            )
        );

        // ...then flush object cache, because transients may be stored there as well.
        wp_cache_flush();
    }


    /**
     * Get transient.
     *
     * @param string ...$key
     *
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
     * @param mixed ...$args
     *
     * @return bool
     */
    public static function setForSite($value, ...$args): bool
    {
        // If the first from variable arguments is plain integer, take it as expiration value.
        $expiration = \is_int($args[0]) ? \array_shift($args) : 0;

        return set_site_transient(self::name($args), $value, $expiration);
    }


    /**
     * Create transient name from $key.
     *
     * @param string[] $key
     *
     * @return string
     */
    private static function name(array $key): string
    {
        return self::NAME_PREFIX . \md5(\implode(':', $key));
    }
}
