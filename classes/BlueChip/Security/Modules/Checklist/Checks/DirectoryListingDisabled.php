<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class DirectoryListingDisabled extends Checklist\BasicCheck
{
    public function __construct()
    {
        parent::__construct(
            __('Directory listing disabled', 'bc-security'),
            sprintf(
                /* translators: 1: link to documentation about DirectoryListings at apache.org */
                esc_html__('A sensible security practice is to disable %1$s.', 'bc-security'),
                '<a href="' . esc_url(__('https://wiki.apache.org/httpd/DirectoryListings', 'bc-security')) . '" rel="noreferrer">' . esc_html__('directory listings', 'bc-security') . '</a>'
            )
        );
    }


    protected function runInternal(): Checklist\CheckResult
    {
        $upload_paths = wp_upload_dir();
        if (!isset($upload_paths['baseurl'])) {
            return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether directory listing is disabled.', 'bc-security'));
        }

        $response = wp_remote_get($upload_paths['baseurl']);
        $response_body = wp_remote_retrieve_body($response);

        return (stripos($response_body, '<title>Index of') === false)
            ? new Checklist\CheckResult(true, esc_html__('It seems that directory listing is disabled.', 'bc-security'))
            : new Checklist\CheckResult(false, esc_html__('It seems that directory listing is not disabled!', 'bc-security'))
        ;
    }
}
