<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Services\ReverseDnsLookup;

/**
 * Simple wrapper for reverse DNS lookup response.
 */
class Response
{
    /**
     * @var string
     */
    private $ip_address;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var array
     */
    private $context;


    /**
     * @param string $ip_address
     * @param string $hostname
     * @param array $context
     */
    public function __construct(string $ip_address, string $hostname, array $context)
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


    public function getContext(): array
    {
        return $this->context;
    }
}
