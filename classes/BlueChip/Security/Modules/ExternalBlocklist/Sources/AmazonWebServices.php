<?php

namespace BlueChip\Security\Modules\ExternalBlocklist\Sources;

use BlueChip\Security\Helpers\IpAddress;
use BlueChip\Security\Helpers\Transients;
use BlueChip\Security\Helpers\WpRemote;
use BlueChip\Security\Modules\ExternalBlocklist\Source;

class AmazonWebServices implements Source
{
    /**
     * @var string Remote URL with Amazon Web Services IP ranges.
     */
    private const REMOTE_URL = 'https://ip-ranges.amazonaws.com/ip-ranges.json';

    /**
     * @var string Transient key for IP ranges.
     */
    private const TRANSIENT_KEY = 'amazon-web-services-ip-ranges';


    public function getIpPrefixes(): array
    {
        return Transients::getForSite(self::TRANSIENT_KEY) ?: [];
    }


    public function getSize(): int
    {
        return \count($this->getIpPrefixes());
    }


    public function warmUp(): void
    {
        $json = WpRemote::getJson(self::REMOTE_URL);

        // Bail on error or if the response body is invalid.
        if (empty($json) || empty($json->prefixes)) {
            return;
        }

        $ip_prefixes = [];
        foreach ($json->prefixes as $instance) {
            $ip_prefixes[] = IpAddress::sanitizePrefix($instance->ip_prefix);
        }

        // Note: store prefixes with a one week time to live.
        // If updating fails at some point, we do not want to block based on outdated data.
        Transients::setForSite($ip_prefixes, WEEK_IN_SECONDS, self::TRANSIENT_KEY);
    }


    public function tearDown(): void
    {
        Transients::deleteFromSite(self::TRANSIENT_KEY);
    }
}
