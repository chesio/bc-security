<?php

namespace BlueChip\Security\Helpers;

/**
 * Wrapper on top of \wp_remote_* methods.
 */
abstract class WpRemote
{
    /**
     * Fetch JSON data from remote $url.
     *
     * @param string $url
     *
     * @return mixed
     */
    public static function getJson(string $url): mixed
    {
        // Make request to URL.
        $response = wp_remote_get($url);

        // Check response code.
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        // Read JSON.
        $json = \json_decode(wp_remote_retrieve_body($response));

        // If decoding went fine, return JSON data.
        return (\json_last_error() === JSON_ERROR_NONE) ? $json : null;
    }


    /**
     * Post given $body data as JSON to remote $url and return decoded response.
     *
     * @param string $url
     * @param mixed $body
     *
     * @return mixed
     */
    public static function postJson(string $url, mixed $body): mixed
    {
        // Make POST request to remote $url.
        $response = wp_remote_post(
            $url,
            [
                'headers' => ['content-type' => 'application/json'],
                'body' => \json_encode($body) ?: '',
            ]
        );

        // Check response code.
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        // Read JSON.
        $json = \json_decode(wp_remote_retrieve_body($response), true);

        // If decoding went fine, return JSON data.
        return (\json_last_error() === JSON_ERROR_NONE) ? $json : null;
    }
}
