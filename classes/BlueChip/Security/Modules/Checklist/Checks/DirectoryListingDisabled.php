<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class DirectoryListingDisabled extends Checklist\Check
{
    public function __construct()
    {
        parent::__construct(
            __('Directory listing disabled', 'bc-security'),
            sprintf(__('A sensible security practice is to disable <a href="%s">directory listings</a>.', 'bc-security'), 'https://wiki.apache.org/httpd/DirectoryListings')
        );
    }


    public function run(): Checklist\CheckResult
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
