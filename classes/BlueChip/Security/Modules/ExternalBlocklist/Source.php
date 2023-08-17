<?php

namespace BlueChip\Security\Modules\ExternalBlocklist;

use BlueChip\Security\Helpers\IpAddress;

/**
 * Base class for all sources for external blocklist.
 */
abstract class Source implements \Countable
{
    /**
     * @var string[] List of IP prefixes for this source.
     */
    protected array $ip_prefixes;

    /**
     * @return int Count of IP prefixes for this source.
     */
    public function count(): int
    {
        return $this->getSize();
    }

    /**
     * @return string[] List of IP prefixes for this source.
     */
    public function getIpPrefixes(): array
    {
        return $this->ip_prefixes;
    }

    /**
     * @param string[] $ip_prefixes List of IP prefixes for this source.
     */
    public function setIpPrefixes(array $ip_prefixes): void
    {
        $this->ip_prefixes = $ip_prefixes;
    }

    /**
     * @return int Count of IP prefixes for this source.
     */
    public function getSize(): int
    {
        return \count($this->ip_prefixes);
    }

    /**
     * @return bool True if source contains given IP address in one of its IP ranges, false otherwise.
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

    /**
     * @return string Human-readable title of this source.
     */
    abstract public function getTitle(): string;

    /**
     * Fetch IP prefixes from remote origin.
     *
     * @return bool True if update succeeded, false otherwise.
     */
    abstract public function updateIpPrefixes(): bool;
}
