<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use BlueChip\Security\Helpers\HaveIBeenPwned;

class HaveIBeenPwnedTest extends \BlueChip\Security\Tests\Integration\TestCase
{
    /**
     * Test integration with Pwned Passwords API.
     *
     * Important: this test results in external requests to https://api.pwnedpasswords.com being made!
     */
    public function testPwnedPassword()
    {
        $this->assertTrue(HaveIBeenPwned::hasPasswordBeenPwned('123456')); // Definitely pwned password...
        $this->assertFalse(HaveIBeenPwned::hasPasswordBeenPwned('This password has not been pwned... yet.'));
    }
}
