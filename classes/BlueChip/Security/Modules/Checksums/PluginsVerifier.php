<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checksums;

use BlueChip\Security\Helpers;
use BlueChip\Security\Modules;

/**
 * Plugins verifier gets (official) checksums from WordPress.org Downloads.
 *
 * @link https://meta.trac.wordpress.org/ticket/3192
 */
class PluginsVerifier extends Verifier implements Modules\Initializable
{
    /**
     * @var string URL of checksum API
     */
    const CHECKSUMS_API_URL_BASE = 'https://downloads.wordpress.org/plugin-checksums/';


    public function init()
    {
        // Hook into cron job execution.
        add_action(Modules\Cron\Jobs::CORE_CHECKSUMS_VERIFIER, [$this, 'runCheck'], 10, 0);
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
        $plugins = apply_filters(Hooks::PLUGINS_TO_VERIFY, Helpers\Plugin::getPluginsInstalledFromWordPressOrg());

        // Plugins for which checksums retrieval failed.
        $checksums_retrieval_failed = [];

        // Plugins for which checksums verification returned positive results.
        $checksums_verification_failed = [];

        foreach ($plugins as $plugin_basename => $plugin_data) {
            $slug = Helpers\Plugin::getSlug($plugin_basename);

            // Add necessary arguments to request URL.
            $url = self::CHECKSUMS_API_URL_BASE . $slug . '/' . $plugin_data['Version'] . '.json';

            // Get checksums.
            if (empty($checksums = $this->getChecksums($url))) {
                $checksums_retrieval_failed[$plugin_basename] = array_merge($plugin_data, ['Checksums URL' => $url]);
                continue;
            }

            // Get absolute path to plugin directory.
            $plugin_dir = trailingslashit(Helpers\Plugin::getPluginDirPath($plugin_basename));

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
}
