<?php
/**
 * PHPUnit bootstrap file for unit tests
 *
 * @package BC Security
 */

// Require Composer autoloader.
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

(new \BlueChip\Security\Tests\Unit\Bootstrap())->run();
