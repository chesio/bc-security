<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class DisplayOfPhpErrorsIsOff extends Checklist\Check
{
    public function __construct()
    {
        parent::__construct(
            __('Display of PHP errors is off', 'bc-security'),
            sprintf(
                __('<a href="%1$s">Errors should never be printed</a> to the screen as part of the output on production systems. In WordPress environment, <a href="%2$s">display of errors can lead to path disclosures</a> when directly loading certain files.', 'bc-security'),
                'http://php.net/manual/en/errorfunc.configuration.php#ini.display-errors',
                'https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-there-path-disclosures-when-directly-loading-certain-files'
            )
        );
    }


    /**
     * Check makes sense only in production environment.
     *
     * @return bool
     */
    public function makesSense(): bool
    {
        return defined('WP_ENV') && (WP_ENV === 'production');
    }


    public function run(): Checklist\CheckResult
    {
        // Craft temporary file name.
        $name = sprintf('bc-security-checklist-test-error-display-%s.php', md5(rand()));

        // The file is going to be created in wp-content directory.
        $path = WP_CONTENT_DIR . '/' . $name;
        $url = WP_CONTENT_URL . '/' . $name;

        // Note: we rely on the fact that empty('0') is true here.
        $php_snippet = "<?php echo empty(ini_get('display_errors')) ? 'OK' : 'KO';";

        $status = new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether display of errors is turned off by default.', 'bc-security'));

        // Write temporary file...
        if (file_put_contents($path, $php_snippet) === false) {
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
        unlink($path);

        // Report on status.
        return $status;
    }
}
