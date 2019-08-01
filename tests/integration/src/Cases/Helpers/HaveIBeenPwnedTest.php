<?php
/**
 * @package BC Security
 */

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use BlueChip\Security\Helpers\HaveIBeenPwned;
use BlueChip\Security\Tests\Integration\Constants;

class HaveIBeenPwnedTest extends \BlueChip\Security\Tests\Integration\TestCase
{
    /**
     * Test integration with Pwned Passwords API.
     *
     * Important: this test results in external requests to https://api.pwnedpasswords.com being made!
     */
    public function testPwnedPassword()
    {
        $this->assertTrue(HaveIBeenPwned::hasPasswordBeenPwned(Constants::PWNED_PASSWORD));
        $this->assertFalse(HaveIBeenPwned::hasPasswordBeenPwned(Constants::SAFE_PASSWORD));
    }
}
