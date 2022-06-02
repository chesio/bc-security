<?php

namespace BlueChip\Security\Tests\Unit\Cases\Helpers;

use BlueChip\Security\Helpers\HaveIBeenPwned;
use BlueChip\Security\Tests\Integration\Constants;
use BlueChip\Security\Tests\Integration\TestCase;

/**
 * Test integration with Pwned Passwords API.
 *
 * @internal These tests result in external requests to https://api.pwnedpasswords.com being made!
 */
class HaveIBeenPwnedTest extends TestCase
{
    /**
     * Test that pwned password is reported as such.
     *
     * @group external
     */
    public function testPwnedPassword()
    {
        $result = HaveIBeenPwned::hasPasswordBeenPwned(Constants::PWNED_PASSWORD);

        if ($result === null) {
            $this->markTestSkipped('Request to api.pwnedpasswords.com failed.');
            return;
        }

        $this->assertTrue($result);
    }


    /**
     * Test that safe password is reported as such.
     *
     * @group external
     */
    public function testSafePassword()
    {
        $result = HaveIBeenPwned::hasPasswordBeenPwned(Constants::SAFE_PASSWORD);

        if ($result === null) {
            $this->markTestSkipped('Request to api.pwnedpasswords.com failed.');
            return;
        }

        $this->assertFalse($result);
    }
}
