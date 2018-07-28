<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers;
use BlueChip\Security\Modules\Checklist;

class NoPluginsRemovedFromDirectory extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    const PLUGINS_DIRECTORY_URL = 'https://wordpress.org/plugins/';

    /**
     * @var string
     */
    const PLUGINS_DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/';


    public function __construct()
    {
        parent::__construct(
            __('No removed plugins installed', 'bc-security'),
            sprintf(__('Plugins can be removed from <a href="%s">Plugins Directory</a> for several reasons (including but no limited to <a href="%s">security vulnerability</a>). Use of removed plugins is discouraged.', 'bc-security'), self::PLUGINS_DIRECTORY_URL, 'https://www.wordfence.com/blog/2017/09/display-widgets-malware/')
        );
    }


    public function run(): Checklist\CheckResult
    {
        // Get filtered list of installed plugins.
        $plugins = apply_filters(
            Checklist\Hooks::PLUGINS_TO_CHECK_AT_WORDPRESS_ORG,
            Helpers\Plugin::getPluginsInstalledFromWordPressOrg()
        );

        // Find the problematic ones.
        $problematic_plugins = $this->getProblematicPlugins($plugins);

        // Format check results into human-readable output.
        $list_of_removed_plugins = $this->implodeList($problematic_plugins['removed_plugins'], true);
        $list_of_unknown_plugins = $this->implodeList($problematic_plugins['unknown_plugins'], false);

        if (!empty($list_of_removed_plugins)) {
            $message = 'Following plugins seem to have been removed from Plugins Directory: ' . $list_of_removed_plugins;
            if (!empty($list_of_unknown_plugins)) {
                // Also report any plugins that could not be checked, just in case.
                $message .= '<br>';
                $message .= 'Furthermore, following plugins could not be checked: ' . $list_of_unknown_plugins;
            }
            return new Checklist\CheckResult(false, $message);
        }

        if (!empty($list_of_unknown_plugins)) {
            $message = 'No removed plugins found, but following plugins could not be checked: ' . $list_of_unknown_plugins;
            return new Checklist\CheckResult(null, $message);
        }

        return new Checklist\CheckResult(true, 'There seems to be no plugins installed that have been removed from Plugins Directory.');
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
            // Try to fetch plugin page.
            $path = Helpers\Plugin::getSlug($plugin_basename);
            $plugin_page_url = trailingslashit(self::PLUGINS_DIRECTORY_URL . $path);
            $response = wp_remote_get($plugin_page_url);

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
            $plugin_download_url_prefix = self::PLUGINS_DOWNLOAD_URL . $path; // Full URL contains version number.
            if ((strpos($body, 'download-button') === false) || (strpos($body, $plugin_download_url_prefix) === false)) {
                $removed_plugins[$plugin_basename] = array_merge($plugin_data, ['DirectoryURI' => $plugin_page_url]);
            }
        }

        return ['removed_plugins' => $removed_plugins, 'unknown_plugins' => $unknown_plugins,];
    }


    /**
     * Create comma separated list of plugin names optionally with a link to plugin page.
     *
     * @param array $list
     * @param bool $linkToPage
     * @return string
     */
    private function implodeList(array $list, bool $linkToPage): string
    {
        return implode(
            ', ',
            array_map(
                function (array $plugin_data) use ($linkToPage): string {
                    return $linkToPage
                        ? '<a href="' . esc_url($plugin_data['DirectoryURI']) . '"><em>' . esc_html($plugin_data['Name']) . '</em></a>'
                        : '<em>' . esc_html($plugin_data['Name']) . '</em>'
                    ;
                },
                $list
            )
        );
    }
}
