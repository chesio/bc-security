<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist\Checks;

use BlueChip\Security\Modules\Checklist;

class NoAccessToPhpFilesInUploadsDirectory extends Checklist\BasicCheck
{
    public function getDescription(): string
    {
        return \sprintf(
            /* translators: 1: link to gist with .htaccess configuration that disables access to PHP files */
            esc_html__('Vulnerable plugins may allow upload of arbitrary files into uploads directory. %1$s within uploads directory may help prevent successful exploitation of such vulnerabilities.', 'bc-security'),
            '<a href="https://gist.github.com/chesio/8f83224840eccc1e80a17fc29babadf2" rel="noreferrer">' . esc_html__('Disabling access to PHP files', 'bc-security') . '</a>'
        );
    }


    public function getName(): string
    {
        return __('No access to PHP files in uploads directory', 'bc-security');
    }


    protected function runInternal(): Checklist\CheckResult
    {
        $php_file_message = 'It is more secure to not allow PHP files to be accessed from within WordPress uploads directory.';

        // Prepare temporary file name and contents.
        $name = \sprintf('bc-security-checklist-test-%s.txt', \md5((string) \rand())); // .txt extension to avoid upload file MIME check killing our test
        $bits = \sprintf('<?php echo "%s";', $php_file_message);

        // Create temporary PHP file in uploads directory.
        $result = wp_upload_bits($name, null, $bits);

        if ($result['error'] !== false) {
            return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether PHP files can be executed from uploads directory.', 'bc-security'));
        }

        // Change file extension to php.
        $file = \substr($result['file'], 0, -3) . 'php';
        if (!\rename($result['file'], $file)) {
            \unlink($result['file']);
            return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether PHP files can be executed from uploads directory.', 'bc-security'));
        }

        $url = \substr($result['url'], 0, -3) . 'php';

        // Check if access to PHP file is forbidden.
        $status = Checklist\Helper::isAccessToUrlForbidden($url, $php_file_message);

        // Remove temporary PHP file from uploads directory
        \unlink($file);

        // Report status
        if (\is_bool($status)) {
            return $status
                ? new Checklist\CheckResult(true, esc_html__('It seems that PHP files cannot be executed from uploads directory.', 'bc-security'))
                : new Checklist\CheckResult(false, esc_html__('It seems that PHP files can be executed from uploads directory!', 'bc-security'))
            ;
        } else {
            return new Checklist\CheckResult(null, esc_html__('BC Security has failed to determine whether PHP files can be executed from uploads directory.', 'bc-security'));
        }
    }
}
