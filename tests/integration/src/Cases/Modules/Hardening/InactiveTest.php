<?php

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Tests\Integration\Constants;

class InactiveTest extends TestCase
{
    public function prepareTest(): void
    {
        // Turn all hardening options off.
        $this->getSettings(false)->persist();
    }


    /**
     * Test the case when no hardening option is active.
     */
    public function testHardeningInactive()
    {
        $this->assertArrayHasKey(
            'pingback.ping',
            apply_filters('xmlrpc_methods', ['pingback.ping' => 'this:pingback_ping', ])
        );

        $this->assertTrue(apply_filters('xmlrpc_enabled', true));

        // Create dummy user object.
        $user_id = $this->factory->user->create();

        /** @var \WP_User $user */
        $user = \get_user_by('id', $user_id);

        // Authentication with both email and login should pass.
        $this->assertInstanceOf(\WP_User::class, \wp_authenticate($user->user_email, Constants::FACTORY_PASSWORD));
        $this->assertInstanceOf(\WP_User::class, \wp_authenticate($user->user_login, Constants::FACTORY_PASSWORD));

        // Test strong password - should pass.
        $this->setUpUserPostData(Constants::SAFE_PASSWORD);
        $this->assertIsInt(\edit_user($user_id));

        // Test weak password - should pass as well.
        $this->setUpUserPostData(Constants::PWNED_PASSWORD);
        $this->assertIsInt(\edit_user($user_id));

        // Clean up.
        $this->tearDownUserPostData();
    }
}
