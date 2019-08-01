<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Modules\Hardening;

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
    }
}
