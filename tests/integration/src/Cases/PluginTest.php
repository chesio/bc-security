<?php

namespace BlueChip\Security\Tests\Integration\Cases;

use BlueChip\Security\Plugin;

class PluginTest extends \BlueChip\Security\Tests\Integration\TestCase
{
    /**
     * @var \BlueChip\Security\Plugin
     */
    protected $bc_security;

    /**
     * Setup test.
     */
    public function setUp()
    {
        global $wpdb;

        parent::setUp();

        $this->bc_security = new Plugin('', $wpdb);
    }

    /**
     * Test class instances.
     */
    public function testInstances()
    {
        $this->assertInstanceOf(Plugin::class, $this->bc_security);
        $this->assertInstanceOf(\wpdb::class, $this->readAttribute($this->bc_security, 'wpdb'));
    }
}
