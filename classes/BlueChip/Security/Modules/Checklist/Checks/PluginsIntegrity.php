<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Cron\Jobs;

class PluginsIntegrity extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    const CRON_JOB_HOOK = Jobs::PLUGINS_INTEGRITY_CHECK;


    public function __construct()
    {
        parent::__construct(
            __('Plugin files are untouched', 'bc-security'),
            sprintf(
                /* translators: 1: link to Wikipedia article about md5sum, 2: link to Plugins Directory at WordPress.org */
                esc_html__('By comparing %1$s of local plugin files with checksums provided by WordPress.org it is possible to determine, if any of plugin files have been modified or if there are any unknown files in plugin directories. Note that this check works only with plugins installed from %2$s.', 'bc-security'),
                '<a href="' . esc_url(__('https://en.wikipedia.org/wiki/Md5sum', 'bc-security')) . '" target="_blank">' . esc_html__('MD5 checksums', 'bc-security') . '</a>',
                '<a href="' . esc_url(__('https://wordpress.org/plugins/', 'bc-security')) . '" target="_blank">' . esc_html__('Plugins Directory', 'bc-security') . '</a>'
            )
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        // Grab a list of plugins to check.
        $plugins = apply_filters(
            Checklist\Hooks::PLUGINS_TO_CHECK_FOR_INTEGRITY,
            Helpers\Plugin::getPluginsInstalledFromWordPressOrg()
        );

        if (defined('WP_ENV') && (WP_ENV === 'development')) {
            // In development, do not check plugins that are under version control.
            $plugins = array_filter($plugins, function (string $plugin_basename): bool {
                return !Helpers\Plugin::isVersionControlled($plugin_basename);
            }, ARRAY_FILTER_USE_KEY);
        }

        // Plugins for which checksums retrieval failed.
        $checksums_retrieval_failed = [];

        // Plugins for which checksums verification returned positive results.
        $checksums_verification_failed = [];

        foreach ($plugins as $plugin_basename => $plugin_data) {
            // Get checksums URL.
            $checksums_url = Helpers\Plugin::getChecksumsUrl($plugin_basename, $plugin_data);
            // Save checksums URL along with plugin data for later.
            $plugin_data['ChecksumsURL'] = $checksums_url;

            // Get checksums.
            if (empty($checksums = $this->getChecksums($checksums_url))) {
                $checksums_retrieval_failed[$plugin_basename] = $plugin_data;
                continue;
            }

            // Get absolute path to plugin directory.
            $plugin_dir = trailingslashit(Helpers\Plugin::getPluginDirPath($plugin_basename));

            // Use checksums to find any modified files.
            $modified_files = Checklist\Helper::checkDirectoryForModifiedFiles($plugin_dir, $checksums, ['readme.txt']);
            // Use checksums to find any unknown files.
            $unknown_files = Checklist\Helper::scanDirectoryForUnknownFiles($plugin_dir, $plugin_dir, $checksums, true);

            // Trigger alert, if any suspicious files have been found.
            if (!empty($modified_files) || !empty($unknown_files)) {
                $checksums_verification_failed[$plugin_basename] = array_merge(
                    $plugin_data,
                    [
                        'ModifiedFiles' => Checklist\Helper::formatListOfFiles($modified_files),
                        'UnknownFiles' => Checklist\Helper::formatListOfFiles($unknown_files),
                    ]
                );
            }
        }

        // Format check results into human-readable output.
        if (!empty($checksums_verification_failed)) {
            $message_parts = [
                esc_html__('The following plugins seem to have been altered in some way.', 'bc-security'),
            ];

            foreach ($checksums_verification_failed as $plugin_basename => $plugin_data) {
                $message_parts[] = '';
                $message_parts[] = sprintf('<strong>%s</strong> <code>%s</code>', esc_html($plugin_data['Name']), $plugin_basename);
                if (!empty($plugin_data['ModifiedFiles'])) {
                    $message_parts[] = sprintf(esc_html__('Modified files: %s', 'bc-security'), $plugin_data['ModifiedFiles']);
                }
                if (!empty($plugin_data['UnknownFiles'])) {
                    $message_parts[] = sprintf(esc_html__('Unknown files: %s', 'bc-security'), $plugin_data['UnknownFiles']);
                }
            }

            if (!empty($checksums_retrieval_failed)) {
                // Also report any plugins that could not be checked, just in case.
                $message_parts[] = '';
                $message_parts[] = sprintf(
                    esc_html__('Furthermore, checksums for the following plugins could not be fetched: %s', 'bc-security'),
                    Helpers\Plugin::implodeList($checksums_retrieval_failed, 'ChecksumsURL')
                );
            }
            return new Checklist\CheckResult(false, $message_parts);
        }

        if (!empty($checksums_retrieval_failed)) {
            $message = sprintf(
                esc_html__('No modified plugins found, but checksums for the following plugins could not be fetched: %s', 'bc-security'),
                Helpers\Plugin::implodeList($checksums_retrieval_failed, 'ChecksumsURL')
            );
            return new Checklist\CheckResult(null, $message);
        }

        return new Checklist\CheckResult(true, esc_html__('There seem to be no altered plugins.', 'bc-security'));
    }


    /**
     * Get md5 checksums of plugin files from downloads.wordpress.org.
     *
     * @param string $url
     * @return \stdClass|null
     */
    private static function getChecksums(string $url)
    {
        $json = Checklist\Helper::getJson($url);

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
