<?php

namespace BlueChip\Security\Tests\Unit;

use Brain\Monkey;

class TestCase extends \PHPUnit\Framework\TestCase
{
    // See: https://github.com/Brain-WP/BrainMonkey/issues/39
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;


    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Mock some more WordPress functions
        // https://brain-wp.github.io/BrainMonkey/docs/functions-stubs.html

        // Functions that always return their first argument.
        Monkey\Functions\stubs(
            [
                '__', // https://github.com/Brain-WP/BrainMonkey/issues/25
            ]
        );

        // Functions that always return false.
        Monkey\Functions\stubs(
            [
                'is_multisite',
            ],
            false
        );

        require_once dirname(__DIR__, 3) . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
    }


    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
