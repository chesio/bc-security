<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Setup;

use BlueChip\Security\Setup\IpAddress;
use BlueChip\Security\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class IpAddressValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Valid IP
        $_SERVER[IpAddress::REMOTE_ADDR] = '23.23.23.23';
        // Cross-Site Scripting attempt
        $_SERVER[IpAddress::HTTP_X_FORWARDED_FOR] = '<span onmouseover=alert(1)>23.23.23.23</span>';
        // Invalid IP with valid format
        $_SERVER[IpAddress::HTTP_X_REAL_IP] = '256.256.256.256';
    }


    protected function tearDown(): void
    {
        unset($_SERVER[IpAddress::REMOTE_ADDR]);
        unset($_SERVER[IpAddress::HTTP_X_FORWARDED_FOR]);
        unset($_SERVER[IpAddress::HTTP_X_REAL_IP]);

        parent::tearDown();
    }


    public static function provideRemoteAddressGetterData(): array
    {
        return [
            'valid IP' => [IpAddress::REMOTE_ADDR, '23.23.23.23'],
            'Cross-Site Scripting attempt' => [IpAddress::HTTP_X_FORWARDED_FOR, ''],
            'Invalid IP with valid format' => [IpAddress::HTTP_X_REAL_IP, ''],
        ];
    }


    #[DataProvider('provideRemoteAddressGetterData')]
    public function testRemoteAddressGetter(string $connection_type, ?string $ip_address): void
    {
        $this->assertSame($ip_address, IpAddress::get($connection_type));
    }
}
