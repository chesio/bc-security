<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Cases\Modules\Hardening;

use BlueChip\Security\Modules\Hardening;

class CoreTest extends \BlueChip\Security\Tests\TestCase
{
    /**
     * Initialize the hardening and optionally make given $options active (ie. set them to true).
     * @param array $options
     */
    protected function initHardening(array $options = [])
    {
        $settings = new Hardening\Settings('bc-security-hardening');

        foreach ($options as $option) {
            $settings[$option] = true;
        }

        (new Hardening\Core($settings))->init();
    }


    /**
     * Test the case when no hardening option is active.
     */
    public function testHardeningInactive()
    {
        // Init with no hardening options active.
        $this->initHardening([]);

        $this->assertArrayHasKey(
            'pingback.ping',
            apply_filters('xmlrpc_methods', ['pingback.ping' => 'this:pingback_ping', ])
        );

        $this->assertTrue(apply_filters('xmlrpc_enabled', true));

        $this->assertNotWPError(apply_filters('rest_authentication_errors', null));
    }


    public function testDisablePingbacks()
    {
        // Init with pingbacks disabled.
        $this->initHardening([Hardening\Settings::DISABLE_PINGBACKS,]);

        $this->assertArrayNotHasKey(
            'pingback.ping',
            apply_filters('xmlrpc_methods', ['pingback.ping' => 'this:pingback_ping', ])
        );
    }


    public function testDisableXmlRpc()
    {
        // Init with XML-RPC disabled.
        $this->initHardening([Hardening\Settings::DISABLE_XML_RPC,]);

        $this->assertFalse(apply_filters('xmlrpc_enabled', true));
    }


    public function testRequiredAuthForRestAccess()
    {
        // Init with REST API disabled (for anonymous users).
        $this->initHardening([Hardening\Settings::DISABLE_REST_API,]);

        $this->assertWPError(apply_filters('rest_authentication_errors', null));
    }
}
