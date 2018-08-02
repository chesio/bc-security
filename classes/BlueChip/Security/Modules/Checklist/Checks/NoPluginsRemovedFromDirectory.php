<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Cron\Jobs;

class NoPluginsRemovedFromDirectory extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    const CRON_JOB_HOOK = Jobs::NO_REMOVED_PLUGINS_CHECK;

    /**
     * @var string
     */
    const PLUGINS_DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/';


    public function __construct()
    {
        parent::__construct(
            __('No removed plugins installed', 'bc-security'),
            sprintf(__('Plugins can be removed from <a href="%s">Plugins Directory</a> for several reasons (including but no limited to <a href="%s">security vulnerability</a>). Use of removed plugins is discouraged.', 'bc-security'), Helpers\Plugin::PLUGINS_DIRECTORY_URL, 'https://www.wordfence.com/blog/2017/09/display-widgets-malware/')
        );
    }


    public function run(): Checklist\CheckResult
    {
        // Get filtered list of installed plugins.
        $plugins = apply_filters(
            Checklist\Hooks::PLUGINS_TO_CHECK_FOR_REMOVAL,
            Helpers\Plugin::getPluginsInstalledFromWordPressOrg()
        );

        // Find the problematic ones.
        $problematic_plugins = $this->getProblematicPlugins($plugins);

        // Format check results into human-readable output.
        $list_of_removed_plugins = Helpers\Plugin::implodeList($problematic_plugins['removed_plugins'], 'DirectoryURL');
        $list_of_unknown_plugins = Helpers\Plugin::implodeList($problematic_plugins['unknown_plugins'], 'DirectoryURL');

        if (!empty($list_of_removed_plugins)) {
            $message = sprintf(
                esc_html__('Following plugins seem to have been removed from Plugins Directory: %s', 'bc-security'),
                $list_of_removed_plugins
            );

            if (!empty($list_of_unknown_plugins)) {
                // Also report any plugins that could not be checked, just in case.
                $message .= '<br>';
                $message .= sprintf(
                    esc_html__('Furthermore, following plugins could not be checked: %s', 'bc-security'),
                    $list_of_unknown_plugins
                );
            }
            return new Checklist\CheckResult(false, $message);
        }

        if (!empty($list_of_unknown_plugins)) {
            $message = sprintf(
                esc_html__('No removed plugins found, but following plugins could not be checked: %s', 'bc-security'),
                $list_of_unknown_plugins
            );
            return new Checklist\CheckResult(null, $message);
        }

        $message = esc_html__('There seems to be no plugins installed that have been removed from Plugins Directory.', 'bc-security');
        return new Checklist\CheckResult(true, $message);
    }


    /**
     * @param array $plugins
     * @return array
     */
    private function getProblematicPlugins(array $plugins): array
    {
        $unknown_plugins = []; // List of plugins for which the check failed to determine the status.
        $removed_plugins = []; // List of plugins for which the check failed.

        foreach ($plugins as $plugin_basename => $plugin_data) {
            $plugin_url = Helpers\Plugin::getDirectoryUrl($plugin_basename);
            // Save plugin URL along with plugin data for later.
            $plugin_data['DirectoryURL'] = $plugin_url;
            // Try to fetch plugin page.
            $response = wp_remote_get($plugin_url);

            if (wp_remote_retrieve_response_code($response) !== 200) {
                // Plugin does not seem to be hosted on WordPress.org.
                $unknown_plugins[$plugin_basename] = $plugin_data;
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                // Response seems to be lost, report plugin as unknown.
                $unknown_plugins[$plugin_basename] = $plugin_data;
                continue;
            }

            // Check response body for presence of "Download" button and download URL prefix.
            // Note: full URL contains the most recent version number, thus check only the prefix.
            $plugin_download_url_prefix = self::PLUGINS_DOWNLOAD_URL . Helpers\Plugin::getSlug($plugin_basename);
            if ((strpos($body, 'download-button') === false) || (strpos($body, $plugin_download_url_prefix) === false)) {
                $removed_plugins[$plugin_basename] = $plugin_data;
            }
        }

        return ['removed_plugins' => $removed_plugins, 'unknown_plugins' => $unknown_plugins,];
    }
}
