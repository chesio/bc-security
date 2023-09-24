<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use BlueChip\Security\Helpers\IpAddress;

class IpAddressTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    public function providePrefixMatchData(): array
    {
        return [
            ['192.168.0.0/32', '192.168.0.0', true],
            ['192.168.0.0/32', '192.168.0.1', false],

            ['192.168.10.0/25', '192.168.10.0', true],
            ['192.168.10.0/25', '192.168.10.127', true],
            ['192.168.10.0/25', '192.168.10.128', false],

            ['192.168.0.0/16', '192.167.255.255', false],
            ['192.168.0.0/16', '192.168.0.0', true],
            ['192.168.0.0/16', '192.168.10.0', true],
            ['192.168.0.0/16', '192.168.255.255', true],
            ['192.168.0.0/16', '192.169.0.0', false],
        ];
    }

    /**
     * @dataProvider providePrefixMatchData
     */
    public function testIpPrefixMatch(string $cidr_range, string $ip_address, bool $result): void
    {
        $this->assertSame($result, IpAddress::matchesPrefix($ip_address, $cidr_range));
    }
}
