<?php

namespace BlueChip\Security\Helpers;

/**
 * Helper methods to deal with installed plugins.
 */
abstract class Plugin
{
    /**
     * @var string URL of checksum API
     */
    const CHECKSUMS_API_URL_BASE = 'https://downloads.wordpress.org/plugin-checksums/';

    /**
     * @var string URL of Plugins Directory.
     */
    const PLUGINS_DIRECTORY_URL = 'https://wordpress.org/plugins/';

    /**
     * @var string Path (although not technically) to changelog page relative to URL of plugin homepage at Plugins Directory.
     */
    const PLUGINS_DIRECTORY_CHANGELOG_PATH = '#developers';


    /**
     * @param string $plugin_basename
     * @return string URL of the plugin changelog page or empty string, if it cannot be determined.
     */
    public static function getChangelogUrl(string $plugin_basename): string
    {
        // By default, changelog URL is unknown.
        $url = '';

        if (self::hasReadmeTxt($plugin_basename)) {
            // Assume that any plugin with readme.txt comes from Plugins Directory.
            $url = self::getDirectoryUrl($plugin_basename) . self::PLUGINS_DIRECTORY_CHANGELOG_PATH;
        }

        // Allow the changelog URL to be filtered.
        return apply_filters(Hooks::PLUGIN_CHANGELOG_URL, $url, $plugin_basename);
    }


    /**
     * @param string $plugin_basename
     * @return string Presumable URL of the plugin in WordPress.org Plugins Directory.
     */
    public static function getDirectoryUrl(string $plugin_basename): string
    {
        return trailingslashit(self::PLUGINS_DIRECTORY_URL . self::getSlug($plugin_basename));
    }


    /**
     * @param string $plugin_basename
     * @param array $plugin_data
     * @return string Presumable URL of the plugin checksums file at WordPress.org.
     */
    public static function getChecksumsUrl(string $plugin_basename, array $plugin_data): string
    {
        return self::CHECKSUMS_API_URL_BASE . self::getSlug($plugin_basename) . '/' . $plugin_data['Version'] . '.json';
    }


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
        $slug = \dirname($plugin_basename);
        // For single-file plugins, return empty string.
        return $slug === '.' ? '' : $slug;
    }


    /**
     * @param string $plugin_basename
     * @return bool True, if there is readme.txt file present in plugin directory, false otherwise.
     */
    public static function hasReadmeTxt(string $plugin_basename): bool
    {
        return \is_file(self::getPluginDirPath($plugin_basename) . '/readme.txt');
    }


    /**
     * @param string $plugin_basename
     * @return bool True, if directory of given plugin seems to be under version control (Subversion or Git).
     */
    public static function isVersionControlled(string $plugin_basename): bool
    {
        $plugin_dir = self::getPluginDirPath($plugin_basename);
        return \is_dir($plugin_dir . '/.git') || \is_dir($plugin_dir . '/.svn');
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
        if (!\function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // There seem to be no easy way to find out if plugin is hosted at WordPress.org repository or not, see:
        // https://core.trac.wordpress.org/ticket/32101

        return \array_filter(
            get_plugins(),
            [self::class, 'hasReadmeTxt'],
            ARRAY_FILTER_USE_KEY
        );
    }


    /**
     * @internal Only use in admin (back-end) context.
     * @param string $plugin_basename
     * @return array
     */
    public static function getPluginData(string $plugin_basename): array
    {
        // Note: get_plugin_data() function is only defined in admin.
        return get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_basename);
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
        return wp_normalize_path(WP_PLUGIN_DIR . '/' . \dirname($plugin_basename));
    }


    /**
     * Create comma separated list of plugin names optionally with a link to plugin related URL.
     *
     * @param array $plugins
     * @param string $linkTo [optional] If provided, plugin name will be turned into link to URL under given data key.
     * @return string
     */
    public static function implodeList(array $plugins, string $linkTo = ''): string
    {
        return \implode(
            ', ',
            \array_map(
                function (array $plugin_data) use ($linkTo): string {
                    $plugin_name = '<em>' . esc_html($plugin_data['Name']) . '</em>';
                    return $linkTo
                        ? '<a href="' . esc_url($plugin_data[$linkTo]) . '" rel="noreferrer">' . $plugin_name . '</a>'
                        : $plugin_name
                    ;
                },
                $plugins
            )
        );
    }
}
