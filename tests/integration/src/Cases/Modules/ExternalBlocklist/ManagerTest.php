<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration\Cases\Modules\ExternalBlocklist;

use BlueChip\Security\Modules\Access\Scope;
use BlueChip\Security\Modules\Cron\Jobs;
use BlueChip\Security\Modules\ExternalBlocklist\Sources\AmazonWebServices;
use BlueChip\Security\Modules\ExternalBlocklist\WarmUpException;
use BlueChip\Security\Settings;
use BlueChip\Security\Setup\IpAddress;
use BlueChip\Security\Tests\Integration\Constants;
use BlueChip\Security\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test integration of external blocklists.
 *
 * @internal These tests result in external requests!
 */
final class ManagerTest extends TestCase
{
    protected function prepareTest(): void
    {
        // Run all tests in this suite with AWS IP address.
        $_SERVER[IpAddress::REMOTE_ADDR] = Constants::AMAZON_WEB_SERVICE_IP_ADDRESS;

        // Activate AWS blocklist for backend login.
        (new Settings())->forExternalBlocklist()->update(AmazonWebServices::class, Scope::ADMIN->value);
    }


    /**
     * Test external blocklist populated with IP prefixes for Amazon Web Services.
     */
    #[Group('external')]
    public function testAmazonWebServicesBlocklist()
    {
        // Refresh external blocklist.
        try {
            do_action(Jobs::EXTERNAL_BLOCKLIST_REFRESH);
        } catch (WarmUpException $e) {
            // In case warm-up of AWS IP prefixes failed, skip the test.
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
