<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Helpers\SafeBrowsingClient;
use BlueChip\Security\Modules\Cron\Jobs;
use BlueChip\Security\Modules\Checklist;
use BlueChip\Security\Setup;

/**
 * Safe Browsing check using Lookup API
 */
class SafeBrowsing extends Checklist\AdvancedCheck
{
    /**
     * @var string
     */
    protected const CRON_JOB_HOOK = Jobs::SAFE_BROWSING_CHECK;


    /**
     * @param string $google_api_key Google API key for project with Safe Browsing API enabled.
     */
    public function __construct(private string $google_api_key)
    {
        $description = [
            sprintf(
                /* translators: 1: link to Google Safe Browsing page */
                esc_html__('Google maintains an updated %1$s like social engineering sites and sites that host malware or unwanted software. Unless you host such a site on purpose, finding website URL on Google Safe Browsing list is a good indicator of compromise.', 'bc-security'),
                '<a href="' . esc_url('https://developers.google.com/safe-browsing/') . '" rel="noreferrer">' . esc_html__('list of unsafe web resources', 'bc-security') . '</a>'
            ),
        ];

        if ($google_api_key === '') {
            $description[] = sprintf(
                /* translators: 1: link to Google Safe Browsing "Get Started" page, 2: (internal) link to plugin setup page */
                esc_html__('Please note that this check requires an %1$s to be configured in %2$s!', 'bc-security'),
                '<a href="' . esc_url('https://developers.google.com/safe-browsing/v4/get-started') . '" rel="noreferrer">' . esc_html__('API key', 'bc-security') . '</a>',
                '<a href="' . Setup\AdminPage::getPageUrl() . '">' . esc_html__('plugin setup', 'bc-security') . '</a>'
            );
        }

        parent::__construct(
            __('Site is not blacklisted by Google', 'bc-security'),
            implode(' ', $description)
        );
    }


    /**
     * Check makes sense only in live environment.
     *
     * @return bool
     */
    public function isMeaningful(): bool
    {
        return Is::live();
    }


    protected function runInternal(): Checklist\CheckResult
    {
        if ($this->google_api_key === '') {
            return new Checklist\CheckResult(null, sprintf(__('Google API key is not configured. Please, <a href="%1$s">configure the API key</a>.', 'bc-security'), Setup\AdminPage::getPageUrl()));
        }

        // Initialize the client.
        $client = new SafeBrowsingClient($this->google_api_key);

        // Get URL to check.
        $url = home_url();

        // Get check result: false means "not on blacklist".
        $result = $client->check($url);

        if ($result === null) {
            return new Checklist\CheckResult(null, __('Request to Safe Browsing API failed.', 'bc-security'));
        } elseif ($result === false) {
            return new Checklist\CheckResult(true, __('Site is not on the Safe Browsing blacklist.', 'bc-security'));
        } else {
            $message = sprintf(
                __('Site is on the Safe Browsing blacklist. You may find more information here: <a href="%1$s" rel="noreferrer">%1$s</a>', 'bc-security'),
                // Strip region from locale to get language code only.
                SafeBrowsingClient::getReportUrl($url, substr(get_locale(), 0, 2))
            );

            return new Checklist\CheckResult(false, $message);
        }
    }
}
