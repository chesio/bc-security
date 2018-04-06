<?php
/**
 * PHPUnit bootstrap file for integration tests
 *
 * @package BC Security
 */

// Require Composer autoloader.
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

(new \BlueChip\Security\Tests\Integration\Bootstrap())->run();
