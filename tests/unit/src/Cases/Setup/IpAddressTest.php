<?php

namespace BlueChip\Security\Tests\Unit\Cases\Setup;

use BlueChip\Security\Setup\IpAddress;

class IpAddressTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER[IpAddress::REMOTE_ADDR] = '1.1.1.1';
        $_SERVER[IpAddress::HTTP_X_FORWARDED_FOR] = '2.2.2.2,3.3.3.3,4.4.4.4';
        $_SERVER[IpAddress::HTTP_X_REAL_IP] = '5.5.5.5';
        $_SERVER[IpAddress::HTTP_CF_CONNECTING_IP] = '6.6.6.6';
    }


    protected function tearDown(): void
    {
        unset($_SERVER[IpAddress::REMOTE_ADDR]);
        unset($_SERVER[IpAddress::HTTP_X_FORWARDED_FOR]);
        unset($_SERVER[IpAddress::HTTP_X_REAL_IP]);
        unset($_SERVER[IpAddress::HTTP_CF_CONNECTING_IP]);

        parent::tearDown();
    }


    public function provideRemoteAddressGetterData(): array
    {
        return [
            'no setting' => ['', '1.1.1.1'],
            'default' => [IpAddress::REMOTE_ADDR, '1.1.1.1'],
            'forwarded for' => [IpAddress::HTTP_X_FORWARDED_FOR, '2.2.2.2'],
            'real IP' => [IpAddress::HTTP_X_REAL_IP, '5.5.5.5'],
            'CloudFlare' => [IpAddress::HTTP_CF_CONNECTING_IP, '6.6.6.6'],
        ];
    }


    /**
     * @dataProvider provideRemoteAddressGetterData
     */
    public function testRemoteAddressGetter(string $connection_type, string $ip_address): void
    {
        $this->assertSame($ip_address, IpAddress::get($connection_type));
    }


    public function provideRemoteAddressGetterFallbackData(): array
    {
        return [
            'forwarded for' => [IpAddress::HTTP_X_FORWARDED_FOR, '1.1.1.1'],
            'real IP' => [IpAddress::HTTP_X_REAL_IP, '1.1.1.1'],
            'CloudFlare' => [IpAddress::HTTP_CF_CONNECTING_IP, '1.1.1.1'],
        ];
    }


    /**
     * Test the case when connection type is set to proxy, but there is actually no proxy info.
     * @dataProvider provideRemoteAddressGetterFallbackData
     */
    public function testRemoteAddressGetterFallback(string $connection_type, string $ip_address): void
    {
        // Make sure the requested connection info is empty.
        unset($_SERVER[$connection_type]);

        $this->assertSame($ip_address, IpAddress::get($connection_type));
    }


    public function provideRawRemoteAddressGetterData(): array
    {
        return [
            'no setting' => ['', ''],
            'default' => [IpAddress::REMOTE_ADDR, '1.1.1.1'],
            'forwarded for' => [IpAddress::HTTP_X_FORWARDED_FOR, '2.2.2.2,3.3.3.3,4.4.4.4'],
            'real IP' => [IpAddress::HTTP_X_REAL_IP, '5.5.5.5'],
            'CloudFlare' => [IpAddress::HTTP_CF_CONNECTING_IP, '6.6.6.6'],
        ];
    }


    /**
     * @dataProvider provideRawRemoteAddressGetterData
     */
    public function testRemoteRawAddressGetter(string $connection_type, string $ip_address): void
    {
        $this->assertSame($ip_address, IpAddress::getRaw($connection_type));
    }
}
