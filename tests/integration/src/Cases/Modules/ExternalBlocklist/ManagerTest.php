<?php

namespace BlueChip\Security\Tests\Unit\Cases\Modules\ExternalBlocklist;

use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\ExternalBlocklist\Settings as ExternalBlocklistSettings;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;
use BlueChip\Security\Settings;
use BlueChip\Security\Setup\IpAddress;
use BlueChip\Security\Tests\Integration\Constants;
use BlueChip\Security\Tests\Integration\TestCase;

/**
 * Test integration of external blocklists.
 *
 * @internal These tests result in external requests!
 */
class ManagerTest extends TestCase
{
    protected function prepareTest(): void
    {
        // Run all tests in this suite with AWS IP address.
        $_SERVER[IpAddress::REMOTE_ADDR] = Constants::AMAZON_WEB_SERVICE_IP_ADDRESS;

        // Warm up AWS blocklist.
        (new AmazonWebServices())->warmUp();

        // Activate AWS blocklist for backend login.
        (new Settings())->forExternalBlocklist()->update(ExternalBlocklistSettings::AMAZON_WEB_SERVICES, Scope::ADMIN);
    }


    /**
     * Test external blocklist populated with IP prefixes for Amazon Web Services.
     *
     * @group external
     */
    public function testAmazonWebServicesBlocklist()
    {
        // In case warm-up of AWS IP prefixes failed, skip the test.
        if ((new AmazonWebServices())->getSize() === 0) {
            $this->markTestSkipped('Populating of external blocklist with AWS IP prefixes failed.');
            return;
        }

        $exception = null;
        try {
            wp_authenticate(Constants::DEFAULT_USERNAME, Constants::FACTORY_PASSWORD);
        } catch (\WPDieException $exception) {
            $this->assertSame(503, $exception->getCode());
        }
        $this->assertInstanceOf(\WPDieException::class, $exception);
    }
}
