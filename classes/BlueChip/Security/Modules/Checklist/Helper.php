<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

abstract class Helper
{
    const PHP_FILE_MESSAGE = 'It is more secure to not allow PHP files to be accessed from within WordPress uploads directory.';

    /**
     * Check, whether it is possible to access debug.log file.
     *
     * @see wp_debug_mode()
     *
     * @return mixed False, if debug.log file can be accessed. True, if debug.log file cannot be accessed. Null, if test failed without getting valid result.
     */
    public static function isAccessToDebugLogForbidden()
    {
        // Path and filename is hardcoded in wp-includes/load.php
        $url = WP_CONTENT_URL . '/debug.log';

        // Report status.
        return self::isAccessToUrlForbidden($url);
    }


    /**
     * Check, whether it is possible to access a temporary PHP file added to uploads directory.
     * @return mixed False, if PHP file can be accessed. True, if PHP file cannot be accessed. Null, if test failed without getting valid result.
     */
    public static function isAccessToPhpFilesInUploadsDirForbidden()
    {
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

        // Check, if access to PHP file is forbidden.
        $status = self::isAccessToUrlForbidden($url, self::PHP_FILE_MESSAGE);

        // Remove temporary PHP file from uploads directory
        unlink($file);

        // Report status
        return $status;
    }


    /**
     * Check, if access to $url is forbidden.
     *
     * Return value is:
     * - true, if $url returns HTTP status 403
     * - false, if $url returns HTTP status 200 and response body is equal to
     *   $body (if given)
     * - null, in all other cases: URL cannot be read, returns other HTTP status
     *   than 200 or 403 or returns HTTP status 200 with a different body than
     *   $body (if given)
     *
     * @param string $url
     * @param string $body
     * @return null|bool
     */
    public static function isAccessToUrlForbidden($url, $body = null)
    {
        // Try to access the uploaded file.
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            // Assume nothing on error.
            return null;
        } else {
            switch ($response['response']['code']) {
                case 200:
                    // Status suggest URL can be accessed, check response body too, if given.
                    return is_string($body) ? (($response['body'] === $body) ? false : null) : false;
                case 403:
                    return true;
                default:
                    // Otherwise assume nothing.
                    return null;
            }
        }
    }
}
