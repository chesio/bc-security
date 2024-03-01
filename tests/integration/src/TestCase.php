<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration;

use BlueChip\Security\Plugin;

/**
 * Base class for all integration tests
 */
abstract class TestCase extends \WP_UnitTestCase
{
    /**
     * @internal Overriding this method from \WP_UnitTestCase_Base class allows our tests to run on PHPUnit 10.
     *
     * @link https://core.trac.wordpress.org/ticket/59486#comment:6
     */
    public function expectDeprecated(): void
    {
        return;
    }


    /**
     * Allow to load the plugin with context customised for particular test suite.
     */
    public function setUp(): void
    {
        global $wpdb;

        parent::setUp(); // !

        // Prepare test: change plugin settings etc.
        $this->prepareTest();

        // Load the plugin.
        (new Plugin(Bootstrap::getPluginRootDirectory(), $wpdb))->load();
    }


    /**
     * Method is executed in setup phase of every test just before the plugin is loaded.
     */
    protected function prepareTest(): void
    {
        // Empty by default, can be overriden in descendants.
    }
}
