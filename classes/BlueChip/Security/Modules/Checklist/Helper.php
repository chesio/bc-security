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
     * @return bool|null False, if error log can be accessed, true otherwise. Null return value means test failed to determine valid result.
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
     * @return bool|null False, if PHP file can be accessed, true otherwise. Null return value means test failed to determine valid result.
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
        // Try to get provided URL. Use HEAD request for simplicity, if response body is of no interest.
        $response = is_string($body) ? wp_remote_get($url) : wp_remote_head($url);

        switch (wp_remote_retrieve_response_code($response)) {
            case 200:
                // Status suggests that URL can be accessed, but check response body too, if given.
                return is_string($body) ? ((wp_remote_retrieve_body($response) === $body) ? false : null) : false;
            case 403:
                // Status suggests that access to URL is forbidden.
                return true;
            case 404:
                // Status suggests that no resource has been found, but access to URL is not forbidden.
                return false;
            default:
                // Otherwise assume nothing.
                return null;
        }
    }


    /**
     * Check, if directory listing is disabled.
     *
     * @internal Credits for the check go to Wordfence Security.
     *
     * @return null|bool True, if directore listings are disabled, false otherwise. Null return value means test failed to determine valid result.
     */
    public static function isDirectoryListingDisabled()
    {
        $upload_paths = wp_upload_dir();
        if (!isset($upload_paths['baseurl'])) {
            return null;
        }

        $response = wp_remote_get($upload_paths['baseurl']);
        $response_body = wp_remote_retrieve_body($response);

        return stripos($response_body, '<title>Index of') === false;
    }


    /**
     * Check, if display of PHP errors is off by default.
     *
     * Method operates by creating temporary PHP file in wp-content directory that only run ini_get('display_errors')
     * and prints either "OK" or "KO" as response based on the configuration value.
     *
     * @return null|bool True, if display_errors is off, false otherwise. Null return value means test failed to determine valid result.
     */
    public static function isErrorsDisplayOff()
    {
        // Craft temporary file name.
        $name = sprintf('bc-security-checklist-test-error-display-%s.php', md5(rand()));

        // The file is going to be created in wp-content directory.
        $path = WP_CONTENT_DIR . '/' . $name;
        $url = WP_CONTENT_URL . '/' . $name;

        // Note: we rely on the fact that empty('0') is true here.
        $php_snippet = "<?php echo empty(ini_get('display_errors')) ? 'OK' : 'KO';";

        // Write temporary file...
        if (file_put_contents($path, $php_snippet) === false) {
            // ...bail on failure.
            return null;
        }

        $status = null;

        // Attempt to fetch the temporary PHP file and retrieve the body.
        switch (wp_remote_retrieve_body(wp_remote_get($url))) {
            case 'OK':
                $status = true;
                break;
            case 'KO':
                $status = false;
                break;
        }

        // Remove temporary PHP file.
        unlink($path);

        // Report on status.
        return $status;
    }
}
