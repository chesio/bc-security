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
    private const CHECKSUMS_API_URL_BASE = 'https://downloads.wordpress.org/plugin-checksums/';

    /**
     * @var string URL of Plugins Directory.
     */
    private const PLUGINS_DIRECTORY_URL = 'https://wordpress.org/plugins/';

    /**
     * @var string Path (although not technically) to changelog page relative to URL of plugin homepage at Plugins Directory.
     */
    private const PLUGINS_DIRECTORY_CHANGELOG_PATH = '#developers';


    /**
     * @param string $plugin_basename
     * @param array $plugin_data
     *
     * @return string URL of the plugin changelog page or empty string if it cannot be determined.
     */
    public static function getChangelogUrl(string $plugin_basename, array $plugin_data): string
    {
        // By default, changelog URL is unknown.
        $url = '';

        if (self::hasReadmeTxt($plugin_basename) && self::hasWordPressOrgUpdateUri($plugin_basename, $plugin_data)) {
            // Assume that any plugin with readme.txt comes from Plugins Directory.
            $url = self::getDirectoryUrl($plugin_basename) . self::PLUGINS_DIRECTORY_CHANGELOG_PATH;
        }

        // Allow the changelog URL to be filtered.
        return apply_filters(Hooks::PLUGIN_CHANGELOG_URL, $url, $plugin_basename);
    }


    /**
     * @param string $plugin_basename
     *
     * @return string Presumable URL of the plugin in WordPress.org Plugins Directory.
     */
    public static function getDirectoryUrl(string $plugin_basename): string
    {
        return trailingslashit(self::PLUGINS_DIRECTORY_URL . self::getSlug($plugin_basename));
    }


    /**
     * @param string $plugin_basename
     * @param array $plugin_data
     *
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
     *
     * @return string Plugin slug or empty string if plugin does not seem to be installed in its own directory.
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
     *
     * @return bool True if there is readme.txt file present in plugin directory, false otherwise.
     */
    public static function hasReadmeTxt(string $plugin_basename): bool
    {
        return \is_file(self::getPluginDirPath($plugin_basename) . '/readme.txt');
    }


    /**
     * Return true if plugin has no Update URI set or if the Update URI has either wordpress.org or w.org as hostname.
     *
     * @param string $plugin_basename
     * @param array $plugin_data
     *
     * @return bool
     */
    public static function hasWordPressOrgUpdateUri(string $plugin_basename, array $plugin_data): bool
    {
        // Compatibility check with older WordPress versions:
        if (!isset($plugin_data['UpdateURI'])) {
            // The field is not available in WordPress 5.7 or older.
            return true;
        }

        $plugin_update_uri = $plugin_data['UpdateURI'];

        if ($plugin_update_uri === '') {
            // If no Update URI is present, WordPress 5.8 return empty string.
            return true;
        }

        $plugin_slug = self::getSlug($plugin_basename);

        if ($plugin_update_uri === "https://wordpress.org/plugins/{$plugin_slug}/") {
            return true;
        }

        if ($plugin_update_uri === "w.org/plugin/{$plugin_slug}") {
            return true;
        }

        return false;
    }


    /**
     * @param string $plugin_basename
     *
     * @return bool True if directory of given plugin seems to be under version control (Subversion or Git).
     */
    public static function isVersionControlled(string $plugin_basename): bool
    {
        $plugin_dir = self::getPluginDirPath($plugin_basename);
        return \is_dir($plugin_dir . '/.git') || \is_dir($plugin_dir . '/.svn');
    }


    /**
     * Get all installed plugins that seems to be hosted at WordPress.org repository = all plugins that:
     * 1. have readme.txt file and
     * 2. either have no Update URI header set or the URI has wordpress.org or w.org in hostname
     *
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

        $wordpress_org_plugins = \array_filter(
            get_plugins(),
            function (array $plugin_data, string $plugin_basename): bool {
                return Plugin::hasWordPressOrgUpdateUri($plugin_basename, $plugin_data);
            },
            ARRAY_FILTER_USE_BOTH
        );

        return \array_filter(
            $wordpress_org_plugins,
            [self::class, 'hasReadmeTxt'],
            ARRAY_FILTER_USE_KEY
        );
    }


    /**
     * @internal Only use in admin (back-end) context.
     *
     * @param string $plugin_basename
     *
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
     *
     * @return string Absolute path to directory where plugin is installed.
     */
    public static function getPluginDirPath(string $plugin_basename): string
    {
        return wp_normalize_path(WP_PLUGIN_DIR . '/' . \dirname($plugin_basename));
    }


    /**
     * Convert list of items with plugin data to list of plugin names that is optionally:
     * - wrapped in a link to plugin related URL
     * - suffixed with additional information
     *
     * Also, plugin name is wrapped in <strong> and additional information in <em> tag.
     *
     * @param array $plugins List of plugin data items
     * @param string $link_to [optional] Wrap plugin name in a link to URL stored under given key.
     * @param string $extend_by [optional] Append text stored under given key to plugin name.
     *
     * @return string[]
     */
    public static function populateList(array $plugins, string $link_to = '', string $extend_by = ''): array
    {
        return \array_map(
            function (array $plugin_data) use ($link_to, $extend_by): string {
                $plugin_name = '<strong>' . esc_html($plugin_data['Name']) . '</strong>';

                $plugin_link = ($link_to && ('' !== ($url = $plugin_data[$link_to] ?? '')))
                    ? ('<a href="' . esc_url($url) . '" rel="noreferrer">' . $plugin_name . '</a>')
                    : $plugin_name
                ;

                $plugin_info = ($extend_by && ('' !== ($info = $plugin_data[$extend_by] ?? '')))
                    ? (': <em>' . $info . '</em>')
                    : ''
                ;

                return $plugin_link . $plugin_info;
            },
            \array_values($plugins) // = convert associative keys to numeric indices
        );
    }
}
