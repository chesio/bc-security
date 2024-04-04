<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use BlueChip\Security\Helpers\Hooks;
use BlueChip\Security\Helpers\Is;
use BlueChip\Security\Tests\Unit\TestCase;

final class IsTest extends TestCase
{
    /**
     * Ensure that `bc-security/filter:is-admin` fires when Is::admin() method is invoked.
     */
    public function testIsAdminHookFires(): void
    {
        Functions\when('user_can')->justReturn(true);

        Is::admin(\Mockery::mock(\WP_User::class));

        $this->assertEquals(1, Filters\applied(Hooks::IS_ADMIN));
    }


    /**
     * Ensure that `bc-security/filter:is-admin` filters return value of Is::admin() and
     * passes \WP_User instance as its second argument.
     */
    public function testIsAdminFilter(): void
    {
        $user = \Mockery::mock(\WP_User::class);

        // User can't.
        Functions\when('user_can')->justReturn(false);
        // In: false; Out: false;
        Filters\expectApplied(Hooks::IS_ADMIN)->once()->with(false, $user)->andReturn(false);
        $this->assertFalse(Is::admin($user));
        // In: false; Out: true;
        Filters\expectApplied(Hooks::IS_ADMIN)->once()->with(false, $user)->andReturn(true);
        $this->assertTrue(Is::admin($user));

        // User can.
        Functions\when('user_can')->justReturn(true);
        // In: true; Out: false;
        Filters\expectApplied(Hooks::IS_ADMIN)->once()->with(true, $user)->andReturn(false);
        $this->assertFalse(Is::admin($user));
        // In: true; Out: true;
        Filters\expectApplied(Hooks::IS_ADMIN)->once()->with(true, $user)->andReturn(true);
        $this->assertTrue(Is::admin($user));
    }
}
