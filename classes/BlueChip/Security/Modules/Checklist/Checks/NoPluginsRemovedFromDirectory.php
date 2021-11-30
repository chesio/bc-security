<?php

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Cron\Jobs;

class NoPluginsRemovedFromDirectory extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    protected const CRON_JOB_HOOK = Jobs::NO_REMOVED_PLUGINS_CHECK;

    /**
     * @var string
     */
    private const PLUGINS_DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/';

    /**
     * @var string Name of key with directory URL data that is added to plugin data array by this check
     */
    public const DIRECTORY_URL_KEY = 'DirectoryURL';

    /**
     * @var string Name of key with plugin notice from WordPress.org that is added to plugin data array by this check
     */
    public const PLUGIN_NOTICE_KEY = 'WordPressOrgPluginNotice';

    /**
     * @var string
     */
    private const PLUGIN_NOTICE_REGEX = '/<div class="plugin-notice[a-z_\- ]*"><p>(.+)<\/p><\/div>/U';


    public function __construct()
    {
        parent::__construct(
            __('No removed plugins installed', 'bc-security'),
            \sprintf(
                /* translators: 1: link to Plugins Directory, 2: link to article on Wordfence blog */
                esc_html__('Plugins can be removed from %1$s for several reasons (including but no limited to %2$s). Use of removed plugins is discouraged.', 'bc-security'),
                '<a href="' . esc_url(__('https://wordpress.org/plugins/', 'bc-security')) . '" rel="noreferrer">' . esc_html__('Plugins Directory', 'bc-security') . '</a>',
                '<a href="https://www.wordfence.com/blog/2017/09/display-widgets-malware/" rel="noreferrer">' . esc_html__('security vulnerability', 'bc-security') . '</a>'
            )
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        // Get filtered list of installed plugins.
        $plugins = apply_filters(
            Checklist\Hooks::PLUGINS_TO_CHECK_FOR_REMOVAL,
            Helpers\Plugin::getPluginsInstalledFromWordPressOrg()
        );

        // Find the problematic ones.
        $problematic_plugins = $this->getProblematicPlugins($plugins);

        // Format check results into human-readable output.
        $list_of_removed_plugins = Helpers\Plugin::populateList($problematic_plugins['removed_plugins'], self::DIRECTORY_URL_KEY, self::PLUGIN_NOTICE_KEY);
        $list_of_unknown_plugins = Helpers\Plugin::populateList($problematic_plugins['unknown_plugins'], self::DIRECTORY_URL_KEY);

        if ($list_of_removed_plugins !== []) {
            $message = \array_merge(
                [
                    esc_html__('Following plugins seem to have been removed from Plugins Directory:', 'bc-security'),
                    '',
                ],
                $list_of_removed_plugins
            );

            if ($list_of_unknown_plugins !== []) {
                // Also report any plugins that could not be checked, just in case.
                $message[] = '';
                $message[] = \sprintf(
                    esc_html__('Furthermore, following plugins could not be checked: %s', 'bc-security'),
                    \implode(', ', $list_of_unknown_plugins)
                );
            }
            return new Checklist\CheckResult(false, $message);
        }

        if ($list_of_unknown_plugins !== []) {
            $message = \sprintf(
                esc_html__('No removed plugins found, but following plugins could not be checked: %s', 'bc-security'),
                \implode(', ', $list_of_unknown_plugins)
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
            $plugin_data[self::DIRECTORY_URL_KEY] = $plugin_url;
            // Try to fetch plugin page.
            // Do not allow redirections, as non-existing slugs are automatically redirected to search page.
            $response = wp_remote_get($plugin_url, ['redirection' => 0]);

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
            if ((\strpos($body, 'download-button') === false) || (\strpos($body, $plugin_download_url_prefix) === false)) {
                // Extract plugin notice with information why the plugin has been removed.
                $plugin_data[self::PLUGIN_NOTICE_KEY] = $this->extractPluginNotice($body);
                $removed_plugins[$plugin_basename] = $plugin_data;
            }
        }

        return ['removed_plugins' => $removed_plugins, 'unknown_plugins' => $unknown_plugins,];
    }


    /**
     * Attempt to extract plugin notice from $body HTML.
     *
     * @param string $body
     * @return string Plugin notice as plain text or empty string on failure.
     */
    private function extractPluginNotice(string $body): string
    {
        $matches = [];

        if (\preg_match(self::PLUGIN_NOTICE_REGEX, $body, $matches)) {
            return \strip_tags($matches[1]);
        }

        return '';
    }
}
