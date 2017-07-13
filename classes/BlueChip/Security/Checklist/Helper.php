<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Checklist;

abstract class Helper
{
    const PHP_FILE_MESSAGE = 'It is more secure to not allow PHP files to be accessed from within WordPress uploads directory.';

    /**
     * Check, whether it is possible to access a temporary PHP file added to uploads directory.
     * @return mixed False, if PHP file can be accessed. True, if PHP file cannot be accessed. Null, if test failed without getting valid result.
     */
    public static function isAccessToPhpFilesInUploadsDirForbidden()
    {
        // Assume test is not decisive by default.
        $status = null;

        // Prepare temporary file name and contents.
        $name = sprintf('bc-security-checklist-test-%s.txt', md5(rand())); // .txt extension to avoid upload file MIME check killing our test
        $bits = sprintf('<?php echo "%s";', self::PHP_FILE_MESSAGE);

        // Create temporary PHP file in uploads directory.
        $result = wp_upload_bits($name, null, $bits);

        if ($result['error'] !== false) {
            return null;
        }

        // Change file extension to php.
        $file = substr($result['file'], 0, -3) . 'php';
        if (!rename($result['file'], $file)) {
            unlink($result['file']);
            return null;
        }

        $url = substr($result['url'], 0, -3) . 'php';

        // Try to access the uploaded file.
        $response = wp_remote_get($url);
        if (!is_wp_error($response)) {
            switch ($response['response']['code']) {
                case 200:
                    // Status suggest PHP can be executed, but check response body too.
                    $status = ($response['body'] === self::PHP_FILE_MESSAGE) ? false : null;
                    break;
                case 403:
                    $status = true;
                    break;
                // Otherwise assume nothing.
            }
        }

        // Remove temporary PHP file from uploads directory
        unlink($file);

        // Report status
        return $status;
    }
}
