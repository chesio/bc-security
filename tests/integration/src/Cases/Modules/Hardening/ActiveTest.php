<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Tests\Integration\Constants;

/**
 * Test hardening with all options on.
 */
class ActiveTest extends TestCase
{
    protected function prepareTest(): void
    {
        parent::prepareTest();

        // Turn all hardening options on.
        $this->setHardening(true);
    }


    /**
     * Test everything that does not require external HTTP requests.
     */
    public function testHardeningActive(): void
    {
        $this->assertArrayNotHasKey(
            'pingback.ping',
            \apply_filters('xmlrpc_methods', ['pingback.ping' => 'pingback_ping', ])
        );

        $this->assertFalse(\apply_filters('xmlrpc_enabled', true));

        // Authentication with both email and login should fail.
        $this->assertWpError(\wp_authenticate(self::DUMMY_USER_EMAIL, Constants::FACTORY_PASSWORD));
        $this->assertWpError(\wp_authenticate(self::DUMMY_USER_LOGIN, Constants::FACTORY_PASSWORD));
    }


    /**
     * Test user editation with password change.
     *
     * @group external
     */
    public function testPasswordChange(): void
    {
        // Test strong password - should pass.
        $this->setUpUserPostData(Constants::SAFE_PASSWORD);
        $this->assertIsInt(\edit_user($this->dummy_user_id));

        // Test weak password - should not pass.
        $this->setUpUserPostData(Constants::PWNED_PASSWORD);
        $this->assertWpError(\edit_user($this->dummy_user_id));

        // Clean up.
        $this->tearDownUserPostData();
    }
}
