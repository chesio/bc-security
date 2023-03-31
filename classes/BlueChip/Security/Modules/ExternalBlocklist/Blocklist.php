<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

class Blocklist
{
    /**
     * @var Source[] List of sources on this blacklist.
     */
    private $sources = [];

    /**
     * Add source to blocklist.
     */
    public function addSource(Source $source): void
    {
        $this->sources[get_class($source)] = $source;
    }

    /**
     * Get source that has given $ip_address or null if no such source exists in this blocklist.
     */
    public function getSource(string $ip_address): ?Source
    {
        foreach ($this->sources as $source) {
            if ($source->hasIpAddress($ip_address)) {
                return $source;
            }
        }

        return null;
    }
}
