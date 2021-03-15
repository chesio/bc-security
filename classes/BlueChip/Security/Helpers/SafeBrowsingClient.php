<?php

namespace BlueChip\Security\Helpers;

/**
 * Simple Safe Browsing Lookup API v4 client
 *
 * @link https://developers.google.com/safe-browsing/v4/lookup-api
 */
class SafeBrowsingClient
{
    /**
     * @var string
     */
    private const CLIENT_ID = 'chesio/bc-security';

    /**
     * @var string
     */
    private const CLIENT_VERSION = '1';

    /**
     * @var string
     */
    private const LOOKUP_API_URL = 'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=%s';

    /**
     * @var string
     */
    private const TRANSPARENCY_REPORT_URL = 'https://transparencyreport.google.com/safe-browsing/search?url=%s&hl=%s';

    /**
     * @var int
     */
    private const MINIMUM_CACHE_LIFETIME = 300;

    /**
     * @var string
     */
    private const TRANSIENT_ID = 'safe-browsing-lookup';


    /**
     * @var string
     */
    private $lookup_url;


    /**
     * @param string $google_api_key Google API key with Safe Browsing API enabled
     */
    public function __construct(string $google_api_key)
    {
        $this->lookup_url = \sprintf(self::LOOKUP_API_URL, $google_api_key);
    }


    /**
     * Check a single URL for presence in Safe Browsing blacklist.
     *
     * @param string $url URL to check.
     * @return bool|null True if URL is on Safe Browsing blacklist, false if it is not, null on error.
     */
    public function check(string $url): ?bool
    {
        if (Transients::getForSite(self::TRANSIENT_ID, $url)) {
            // URL has been found on blacklist and cached response is valid yet.
            return true;
        }

        // Lookup single URL.
        $matches = $this->lookup([$url]);

        if ($matches === null) {
            return null;
        }

        if ($matches === []) {
            return false;
        }

        // Calculate cache duration.
        $cache_duration = self::MINIMUM_CACHE_LIFETIME;

        foreach ($matches as $match) {
            $duration = $this->getCacheDuration($match);
            if (\is_int($duration) && ($duration > $cache_duration)) {
                $cache_duration = $duration;
            }
        }

        // Cache the positive result for $cache_duration seconds.
        Transients::setForSite(true, $cache_duration, self::TRANSIENT_ID, $url);

        return true;
    }


    /**
     * @param array $urls List of URLs to look up.
     * @return array|null List of matches (empty means no matches found) or null on error.
     */
    public function lookup(array $urls): ?array
    {
        $response = WpRemote::postJson(
            $this->lookup_url,
            self::getRequestBody($urls)
        );

        if ($response === null) {
            // There has been an error, bail.
            return null;
        }

        if (!(\is_object($response) && isset($response->matches))) {
            // No match (empty response), URL is not blacklisted.
            return [];
        }

        return \is_array($response->matches) ? $response->matches : null;
    }


    /**
     * Format transparency report URL for given website $url.
     *
     * @param string $url
     * @param string $lang
     * @return string
     */
    public static function getReportUrl(string $url, string $lang = 'en'): string
    {
        return \sprintf(self::TRANSPARENCY_REPORT_URL, \urlencode($url), $lang);
    }


    /**
     * @todo Maybe this wheel has already been invented?
     *
     * @param object $match
     * @return int|null Cache lifetime for $match in seconds or null if unknown.
     */
    private static function getCacheDuration(object $match): ?int
    {
        if (!isset($match->cacheDuration)) {
            return null;
        }

        $matches = [];

        if (\preg_match('/^(\d+(\.\d+)?)s$/i', $match->cacheDuration, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }


    /**
     * @link https://developers.google.com/safe-browsing/v4/lookup-api#http-post-request
     *
     * @param array $urls List of URLs to check
     * @return array Safe Browsing request data
     */
    private static function getRequestBody(array $urls): array
    {
        $threatEntries = \array_map(function (string $url): array {
            return ['url' => \urlencode($url)];
        }, $urls);

        return [
            'client' => [
                'clientId' => self::CLIENT_ID,
                'clientVersion' => self::CLIENT_VERSION,
            ],
            'threatInfo' => [
                'threatTypes' => [
                    'MALWARE',
                    'UNWANTED_SOFTWARE',
                    'SOCIAL_ENGINEERING',
                    'POTENTIALLY_HARMFUL_APPLICATION',
                    'THREAT_TYPE_UNSPECIFIED',
                ],
                'platformTypes' => [
                    'ANY_PLATFORM',
                ],
                'threatEntryTypes' => [
                    'URL',
                ],
                'threatEntries' => $threatEntries,
            ],
        ];
    }
}
