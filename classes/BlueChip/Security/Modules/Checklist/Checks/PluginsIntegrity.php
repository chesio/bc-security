<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

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


    public function run(): Checklist\CheckResult
    {
        return new Checklist\CheckResult(null, '<em>Not yet implemented.</em>');
    }
}
