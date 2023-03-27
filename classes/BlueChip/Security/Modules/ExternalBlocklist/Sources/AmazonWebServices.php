<?php

namespace BlueChip\Security\Modules\ExternalBlocklist\Sources;

use BlueChip\Security\Helpers\IpAddress;
use BlueChip\Security\Helpers\WpRemote;
use BlueChip\Security\Modules\ExternalBlocklist\Source;

class AmazonWebServices extends Source
{
    /**
     * @var string Remote URL with Amazon Web Services IP ranges.
     */
    private const REMOTE_URL = 'https://ip-ranges.amazonaws.com/ip-ranges.json';


    public function getTitle(): string
    {
        return 'Amazon Web Services IP ranges';
    }


    public function updateIpPrefixes(): bool
    {
        $json = WpRemote::getJson(self::REMOTE_URL);

        // Bail on error or if the response body is invalid.
        if (empty($json) || empty($json->prefixes)) {
            return false;
        }

        $this->ip_prefixes = [];
        foreach ($json->prefixes as $aws_instance) {
            $this->ip_prefixes[] = IpAddress::sanitizePrefix($aws_instance->ip_prefix);
        }

        return true;
    }
}
