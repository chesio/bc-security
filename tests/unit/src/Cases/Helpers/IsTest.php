<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use BlueChip\Security\Helpers;

class IsTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    /**
     * Ensure that `bc-security/filter:is-admin` fires when Helpers\Is::admin() method is invoked.
     */
    public function testIsAdminHookFires()
    {
        Functions\when('user_can')->justReturn(true);

        Helpers\Is::admin(\Mockery::mock(\WP_User::class));

        $this->assertEquals(1, Filters\applied(Helpers\Hooks::IS_ADMIN));
    }


    /**
     * Ensure that `bc-security/filter:is-admin` filters return value of Helpers\Is::admin().
     */
    public function testIsAdminFilter()
    {
        $user = \Mockery::mock(\WP_User::class);

        // User can't.
        Functions\when('user_can')->justReturn(false);
        // In: false; Out: false;
        Filters\expectApplied(Helpers\Hooks::IS_ADMIN)->once()->with(false)->andReturn(false);
        $this->assertFalse(Helpers\Is::admin($user));
        // In: false; Out: true;
        Filters\expectApplied(Helpers\Hooks::IS_ADMIN)->once()->with(false)->andReturn(true);
        $this->assertTrue(Helpers\Is::admin($user));

        // User can.
        Functions\when('user_can')->justReturn(true);
        // In: true; Out: false;
        Filters\expectApplied(Helpers\Hooks::IS_ADMIN)->once()->with(true)->andReturn(false);
        $this->assertFalse(Helpers\Is::admin($user));
        // In: true; Out: true;
        Filters\expectApplied(Helpers\Hooks::IS_ADMIN)->once()->with(true)->andReturn(true);
        $this->assertTrue(Helpers\Is::admin($user));
    }
}
