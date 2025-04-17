<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Modules\Checklist;

class DisplayOfPhpErrorsIsOff extends Checklist\BasicCheck
{
    public function getDescription(): string
    {
        return \sprintf(
            /* translators: 1: link to PHP manual documentation on display-errors php.ini setting, 2: link to WordPress Handbook article */
            esc_html__('%1$s to the screen as part of the output on live system. In WordPress environment, %2$s when directly loading certain files.', 'bc-security'),
            '<a href="' . esc_url(__('https://www.php.net/manual/en/errorfunc.configuration.php#ini.display-errors', 'bc-security')) . '" rel="noreferrer">' . esc_html__('Errors should never be printed', 'bc-security') . '</a>',
            '<a href="' . esc_url(__('https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-there-path-disclosures-when-directly-loading-certain-files', 'bc-security')) . '" rel="noreferrer">' . esc_html__('display of errors can lead to path disclosures', 'bc-security') . '</a>'
        );
    }


    public function getName(): string
    {
        return __('Display of PHP errors is off', 'bc-security');
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
        // Craft temporary file name.
        $name = \sprintf('bc-security-checklist-test-error-display-%s.php', \md5((string) \rand()));

        // The file is going to be created in wp-content directory.
        $path = WP_CONTENT_DIR . '/' . $name;
        $url = WP_CONTENT_URL . '/' . $name;

        // Note: we rely on the fact that empty('0') is true here.
        $php_snippet = "<?php echo empty(ini_get('display_errors')) ? 'OK' : 'KO';";

        $status = new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether display of errors is turned off by default.', 'bc-security'));

        // Write temporary file...
        if (\file_put_contents($path, $php_snippet) === false) {
            // ...bail on failure.
            return $status;
        }

        // Attempt to fetch the temporary PHP file and retrieve the body.
        switch (wp_remote_retrieve_body(wp_remote_get($url))) {
            case 'OK':
                $status = new Checklist\CheckResult(true, esc_html__('It seems that display of errors is turned off by default.', 'bc-security'));
                break;
            case 'KO':
                $status = new Checklist\CheckResult(false, esc_html__('It seems that display of errors is turned on by default!', 'bc-security'));
                break;
        }

        // Remove temporary PHP file.
        \unlink($path);

        // Report on status.
        return $status;
    }
}
