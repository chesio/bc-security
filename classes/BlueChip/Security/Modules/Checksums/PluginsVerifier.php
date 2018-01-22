<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checksums;

/**
 * Plugins verifier gets (official) checksums from WordPress.org Downloads.
 *
 * @link https://meta.trac.wordpress.org/ticket/3192
 */
class PluginsVerifier extends Verifier
{
    /**
     * @var string URL of checksum API
     */
    const CHECKSUMS_API_URL_BASE = 'https://downloads.wordpress.org/plugin-checksums/';


    /**
     * Get absolute path to plugin directory for given $plugin_basename (for example "bc-security/bc-security.php").
     * Obviously, the method works only for plugins that are in their own directory...
     *
     * @see get_plugins()
     *
     * @param string $plugin_basename
     * @return string
     */
    public static function getPluginDirPath(string $plugin_basename): string
    {
        return wp_normalize_path(WP_PLUGIN_DIR . '/' . dirname($plugin_basename));
    }


    /**
     * Perform checksums check.
     *
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::PLUGIN_CHECKSUMS_RETRIEVAL_FAILED
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::PLUGIN_CHECKSUMS_VERIFICATION_ALERT
     * @hook \BlueChip\Security\Modules\Checksums\Hooks::PLUGINS_TO_VERIFY
     */
    public function runCheck()
    {
        // Grab a list of plugins to check.
        $plugins = apply_filters(Hooks::PLUGINS_TO_VERIFY, $this->getPlugins());

        // Plugins for which checksums retrieval failed.
        $checksums_retrieval_failed = [];

        // Plugins for which checksums verification returned positive results.
        $checksums_verification_failed = [];

        foreach ($plugins as $plugin_basename => $plugin_data) {

            // This is fine most of the time and WPCentral/WP-CLI-Security gets the slug the same way,
            // but it does not seem to be guaranteed that slug is always equal to directory name...
            $slug = dirname($plugin_basename);

            // Add necessary arguments to request URL.
            $url = self::CHECKSUMS_API_URL_BASE . $slug . '/' . $plugin_data['Version'] . '.json';

            // Get checksums.
            if (empty($checksums = $this->getChecksums($url))) {
                $checksums_retrieval_failed[$plugin_basename] = array_merge($plugin_data, ['Checksums URL' => $url]);
                continue;
            }

            // Get absolute path to plugin directory.
            $plugin_dir = trailingslashit(self::getPluginDirPath($plugin_basename));

            // Use checksums to find any modified files.
            $modified_files = self::checkDirectoryForModifiedFiles($plugin_dir, $checksums, ['readme.txt']);
            // Use checksums to find any unknown files.
            $unknown_files = self::scanDirectoryForUnknownFiles($plugin_dir, $plugin_dir, $checksums, true);

            // Trigger alert, if any suspicious files have been found.
            if (!empty($modified_files) || !empty($unknown_files)) {
                $checksums_verification_failed[$plugin_basename] = array_merge(
                    $plugin_data,
                    ['ModifiedFiles' => $modified_files, 'UnknownFiles' => $unknown_files]
                );
            }
        }

        if (!empty($checksums_retrieval_failed)) {
            do_action(Hooks::PLUGIN_CHECKSUMS_RETRIEVAL_FAILED, $checksums_retrieval_failed);
        }

        if (!empty($checksums_verification_failed)) {
            do_action(Hooks::PLUGIN_CHECKSUMS_VERIFICATION_ALERT, $checksums_verification_failed);
        }
    }


    /**
     * Get md5 checksums of plugin files from downloads.wordpress.org.
     *
     * @param string $url
     * @return \stdClass|null
     */
    private function getChecksums(string $url)
    {
        $json = self::getJson($url);

        // Bail on error or if the response body is invalid.
        if (empty($json) || empty($json->files)) {
            return null;
        }

        // Return checksums as hashmap (stdClass): filename -> checksum.
        $checksums = [];
        foreach ($json->files as $filename => $file_checksums) {
            $checksums[$filename] = $file_checksums->md5;
        }
        return (object) $checksums;
    }


    /**
     * Get all installed plugins that seems to be hosted at WordPress.org repository (= have readme.txt file).
     *
     * Note: Method effectively filters out any plugins that are not in their own directory (like Hello Dolly).
     *
     * @return array
     */
    private function getPlugins(): array
    {
        // We're using some wp-admin stuff here, so make sure it's available.
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // There seem to be no easy way to find out if plugin is hosted at WordPress.org repository or not, see:
        // https://core.trac.wordpress.org/ticket/32101

        // To not leak data about installed plugins unnecessarily, only keep (check) plugins that have readme.txt file.
        return array_filter(
            get_plugins(),
            function ($plugin_file) {
                return is_file(self::getPluginDirPath($plugin_file) . '/readme.txt');
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
