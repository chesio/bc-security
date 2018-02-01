<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

abstract class Helper
{
    /**
     * Check, if HTTP request to $url results in 403 forbidden response.
     *
     * Method returns:
     * - true, if HTTP request to $url returns HTTP status 403.
     * - false, if HTTP request to $url returns HTTP status 200 and response body is equal to $body (if given) or 404
     *   is returned (meaning file does not exist, but access is not forbidden).
     * - null, in all other cases: especially if HTTP request to $url fails or other HTTP status than 200, 403 or 404
     *   is returned. Null is also returned for HTTP status 200 if response body is different than $body (if given).
     *
     * @param string $url URL to check.
     * @param string $body Response body to check [optional].
     * @return null|bool
     */
    public static function isAccessToUrlForbidden(string $url, $body = null)
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
}
