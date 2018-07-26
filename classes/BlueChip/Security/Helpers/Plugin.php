<?php
/**
 * @package BC_Security
 */
namespace BlueChip\Security\Helpers;


/**
 * Helper methods to deal with installed plugins.
 */
abstract class Plugin
{
    /**
     * Get slug (ie. bc-security) for plugin with given basename (ie. bc-security/bc-security.php).
     *
     * @param string $plugin_basename
     * @return string Plugin slug or empty string, if plugin does not seem to be installed in its own directory.
     */
    public static function getSlug(string $plugin_basename): string
    {
        // This is fine most of the time and WPCentral/WP-CLI-Security gets the slug the same way,
        // but it does not seem to be guaranteed that slug is always equal to directory name...
        $slug = dirname($plugin_basename);
        // For single-file plugins, return empty string.
        return $slug === '.' ? '' : $slug;
    }


    /**
     * @param string $plugin_basename
     * @return bool True, if there is readme.txt file present in plugin directory, false otherwise.
     */
    public static function hasReadmeTxt(string $plugin_basename): bool
    {
        return is_file(self::getPluginDirPath($plugin_basename) . '/readme.txt');
    }


    /**
     * Get all installed plugins that seems to be hosted at WordPress.org repository (= have readme.txt file).
     * Method effectively discards any plugins that are not in their own directory (like Hello Dolly) from output.
     *
     * @return array
     */
    public static function getPluginsInstalledFromWordPressOrg(): array
    {
        // We're using some wp-admin stuff here, so make sure it's available.
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // There seem to be no easy way to find out if plugin is hosted at WordPress.org repository or not, see:
        // https://core.trac.wordpress.org/ticket/32101

        return array_filter(
            get_plugins(),
            [self::class, 'hasReadmeTxt'],
            ARRAY_FILTER_USE_KEY
        );
    }


    /**
     * Get absolute path to plugin directory for given $plugin_basename (ie. "bc-security/bc-security.php").
     *
     * @see get_plugins()
     *
     * @param string $plugin_basename Basename of plugin installed in its own directory.
     * @return string Absolute path to directory where plugin is installed.
     */
    public static function getPluginDirPath(string $plugin_basename): string
    {
        return wp_normalize_path(WP_PLUGIN_DIR . '/' . dirname($plugin_basename));
    }
}
