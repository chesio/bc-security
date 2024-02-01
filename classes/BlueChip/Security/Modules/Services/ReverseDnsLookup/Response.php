<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Services\ReverseDnsLookup;

/**
 * Simple wrapper for reverse DNS lookup response.
 */
class Response
{
    /**
     * @param string $ip_address
     * @param string $hostname
     * @param array<string,mixed> $context
     */
    public function __construct(private string $ip_address, private string $hostname, private array $context)
    {
        $this->ip_address = $ip_address;
        $this->hostname = $hostname;
        $this->context = $context;
    }


    public function getIpAddress(): string
    {
        return $this->ip_address;
    }


    public function getHostname(): string
    {
        return $this->hostname;
    }


    /**
     * @return array<string,mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
