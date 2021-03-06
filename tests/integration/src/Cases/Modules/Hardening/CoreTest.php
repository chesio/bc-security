<?php

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Modules\Hardening;
use BlueChip\Security\Tests\Integration\Constants;

class CoreTest extends \BlueChip\Security\Tests\Integration\TestCase
{
    /**
     * Initialize the hardening with either active or non active state..
     * @param bool $active
     */
    protected function initHardening(bool $active)
    {
        // Mock settings object, make it always return given active state.
        $settings = \Mockery::mock(Hardening\Settings::class, ['offsetGet' => $active]);

        (new Hardening\Core($settings))->init();
    }


    /**
     * Set up $_POST data necessary to test via \edit_user() function.
     * @param bool $pwned Whether to set weak password (true) or strong password (false).
     */
    protected function setUpUserPostData(bool $pwned = false)
    {
        // To be able to test \edit_user() method.
        $_POST['nickname'] = 'John Doe';
        $_POST['email'] = 'john@doe.com';
        $_POST['pass1'] = $pwned ? Constants::PWNED_PASSWORD : Constants::SAFE_PASSWORD;
        $_POST['pass2'] = $pwned ? Constants::PWNED_PASSWORD : Constants::SAFE_PASSWORD;
    }


    /**
     * Clean up $_POST data set in setUpUserPostData() method.
     */
    protected function tearDownUserPostData()
    {
        unset($_POST['nickname']);
        unset($_POST['email']);
        unset($_POST['pass1']);
        unset($_POST['pass2']);
    }


    /**
     * Test the case when hardening options are active.
     */
    public function testHardeningActive()
    {
        $this->initHardening(true);

        $this->assertArrayNotHasKey(
            'pingback.ping',
            apply_filters('xmlrpc_methods', ['pingback.ping' => 'pingback_ping', ])
        );

        $this->assertFalse(apply_filters('xmlrpc_enabled', true));

        // Create dummy user object.
        $user_id = $this->factory->user->create();

        // Test strong password - should pass.
        $this->setUpUserPostData(false);
        $this->assertIsInt(\edit_user($user_id));

        // Test weak password - should not pass.
        $this->setUpUserPostData(true);
        $this->assertWpError(\edit_user($user_id));

        // Clean up.
        $this->tearDownUserPostData();
    }


    /**
     * Test the case when no hardening option is active.
     */
    public function testHardeningInactive()
    {
        $this->initHardening(false);

        $this->assertArrayHasKey(
            'pingback.ping',
            apply_filters('xmlrpc_methods', ['pingback.ping' => 'this:pingback_ping', ])
        );

        $this->assertTrue(apply_filters('xmlrpc_enabled', true));

        // Create dummy user object.
        $user_id = $this->factory->user->create();

        // Test strong password - should pass.
        $this->setUpUserPostData(false);
        $this->assertIsInt(\edit_user($user_id));

        // Test weak password - should pass as well.
        $this->setUpUserPostData(true);
        $this->assertIsInt(\edit_user($user_id));

        // Clean up.
        $this->tearDownUserPostData();
    }
}
