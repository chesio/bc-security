<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Modules\Cron\Jobs;

class CoreIntegrity extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    const CRON_JOB_HOOK = Jobs::CORE_INTEGRITY_CHECK;

    /**
     * @var string URL of checksum API
     */
    const CHECKSUMS_API_URL = 'https://api.wordpress.org/core/checksums/1.0/';


    public function __construct()
    {
        parent::__construct(
            __('WordPress core files are untouched', 'bc-security'),
            sprintf(
                /* translators: 1: link to Wikipedia article about md5sum, 2: link to checksums file at WordPress.org */
                esc_html__('By comparing %1$s of local core files with %2$s it is possible to determine, if any of core files have been modified or if there are any unknown files in core directories.', 'bc-security'),
                '<a href="' . esc_url(__('https://en.wikipedia.org/wiki/Md5sum', 'bc-security')) . '" target="_blank">' . esc_html__('MD5 checksums', 'bc-security') . '</a>',
                '<a href="' . esc_url(self::getChecksumsUrl()) . '" target="_blank">' . esc_html__('checksums downloaded from WordPress.org', 'bc-security') . '</a>'
            )
        );
    }


    public function run(): Checklist\CheckResult
    {
        return new Checklist\CheckResult(null, '<em>Not yet implemented.</em>');
    }


    /**
     * @return string URL to checksums file at api.wordpress.org for current WordPress version and locale.
     */
    public static function getChecksumsUrl(): string
    {
        // Add necessary arguments to request URL.
        return add_query_arg(
            [
                'version' => get_bloginfo('version'),
                'locale'  => get_locale(), // TODO: What about multilanguage sites?
            ],
            self::CHECKSUMS_API_URL
        );
    }
}
