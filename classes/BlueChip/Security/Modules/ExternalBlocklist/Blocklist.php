<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Helpers\IpAddress;

class Blocklist implements \Countable
{
    /**
     * @var string[]
     */
    private $ip_prefixes = [];

    /**
     * Add IP prefixes from $source.
     */
    public function addIpPrefixes(Source $source): void
    {
        $this->ip_prefixes = \array_merge($this->ip_prefixes, $source->getIpPrefixes());
    }

    public function count(): int
    {
        return \count($this->ip_prefixes);
    }

    /**
     * @return bool True if blocklist contains given IP address in it, false otherwise.
     */
    public function hasIpAddress(string $ip_address): bool
    {
        foreach ($this->ip_prefixes as $ip_prefix) {
            if (IpAddress::matchesPrefix($ip_address, $ip_prefix)) {
                return true;
            }
        }

        return false;
    }
}
