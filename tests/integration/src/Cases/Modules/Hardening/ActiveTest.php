<?php

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Tests\Integration\Constants;

/**
 * Test hardening with all options active
 */
class ActiveTest extends TestCase
{
    public function prepareTest(): void
    {
        // Turn all hardening options on.
        $this->getSettings(true)->persist();
    }


    /**
     * Test the case when hardening options are active.
     */
    public function testHardeningActive()
    {
        $this->assertArrayNotHasKey(
            'pingback.ping',
            apply_filters('xmlrpc_methods', ['pingback.ping' => 'pingback_ping', ])
        );

        $this->assertFalse(apply_filters('xmlrpc_enabled', true));

        // Create dummy user object.
        $user_id = $this->factory->user->create();

        /** @var \WP_User $user */
        $user = \get_user_by('id', $user_id);

        // Authentication with both email and login should fail.
        $this->assertWpError(\wp_authenticate($user->user_email, Constants::FACTORY_PASSWORD));
        $this->assertWpError(\wp_authenticate($user->user_login, Constants::FACTORY_PASSWORD));

        // Test strong password - should pass.
        $this->setUpUserPostData(Constants::SAFE_PASSWORD);
        $this->assertIsInt(\edit_user($user_id));

        // Test weak password - should not pass.
        $this->setUpUserPostData(Constants::PWNED_PASSWORD);
        $this->assertWpError(\edit_user($user_id));

        // Clean up.
        $this->tearDownUserPostData();
    }
}
