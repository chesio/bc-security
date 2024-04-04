<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Tests\Integration\Constants;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test hardening with all options off.
 */
final class InactiveTest extends TestCase
{
    protected function prepareTest(): void
    {
        parent::prepareTest();

        // Turn all hardening options off.
        $this->setHardening(false);
    }


    /**
     * Test everything that does not require external HTTP requests.
     */
    public function testHardeningInactive(): void
    {
        $this->assertArrayHasKey(
            'pingback.ping',
            \apply_filters('xmlrpc_methods', ['pingback.ping' => 'this:pingback_ping', ])
        );

        $this->assertTrue(\apply_filters('xmlrpc_enabled', true));

        // Authentication with both email and login should pass.
        $this->assertInstanceOf(\WP_User::class, \wp_authenticate(self::DUMMY_USER_EMAIL, Constants::FACTORY_PASSWORD));
        $this->assertInstanceOf(\WP_User::class, \wp_authenticate(self::DUMMY_USER_LOGIN, Constants::FACTORY_PASSWORD));
    }


    /**
     * Test user editation with password change.
     */
    #[Group('external')]
    public function testPasswordChange(): void
    {
        // Test strong password - should pass.
        $this->setUpUserPostData(Constants::SAFE_PASSWORD);
        $this->assertIsInt(\edit_user($this->dummy_user_id));

        // Test weak password - should pass as well.
        $this->setUpUserPostData(Constants::PWNED_PASSWORD);
        $this->assertIsInt(\edit_user($this->dummy_user_id));

        // Clean up.
        $this->tearDownUserPostData();
    }
}
