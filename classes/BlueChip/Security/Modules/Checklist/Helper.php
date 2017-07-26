<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

abstract class Helper
{
    /**
     * Check, whether it is possible to access WordPress error log file via HTTP.
     *
     * @see wp_debug_mode()
     *
     * @return bool|null False, if error log can be accessed, true otherwise.
     *                   Null return value means test failed to determine valid result.
     */
    public static function isAccessToErrorLogForbidden()
    {
        // Path and filename is hardcoded in wp-includes/load.php
        $url = WP_CONTENT_URL . '/debug.log';

        // Report status.
        return self::isAccessToUrlForbidden($url);
    }


    /**
     * Check, whether it is possible to access a temporary PHP file added to uploads directory.
     *
     * @return mixed False, if PHP file can be accessed, true otherwise.
     *               Null return value means test failed to determine valid result.
     */
    public static function isAccessToPhpFilesInUploadsDirForbidden()
    {
        $php_file_message = 'It is more secure to not allow PHP files to be accessed from within WordPress uploads directory.';

        // Prepare temporary file name and contents.
        $name = sprintf('bc-security-checklist-test-%s.txt', md5(rand())); // .txt extension to avoid upload file MIME check killing our test
        $bits = sprintf('<?php echo "%s";', $php_file_message);

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
        $status = self::isAccessToUrlForbidden($url, $php_file_message);

        // Remove temporary PHP file from uploads directory
        unlink($file);

        // Report status
        return $status;
    }


    /**
     * Check, if HTTP request to $url results in 403 forbidden response.
     *
     * Method returns:
     * - true, if HTTP request to $url returns HTTP status 403.
     * - false, if HTTP request to $url returns HTTP status 200 and response body is equal to $body (if given) or 404
     *   is returned (meaning file does not exist, but access is not forbidden).
     * - null, in all other cases: especially if HTTP request to $url fails or other HTTP status than 200, 403 or 404
     *   is returned. Null is also returned for HTTP status 200 if response body is different than $body (if given).
     *   $body (if given)
     *
     * @param string $url URL to check.
     * @param string $body Response body to check [optional].
     * @return null|bool
     */
    public static function isAccessToUrlForbidden($url, $body = null)
    {
        // Try to access provided URL.
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
                case 404:
                    return false;
                default:
                    // Otherwise assume nothing.
                    return null;
            }
        }
    }
}
