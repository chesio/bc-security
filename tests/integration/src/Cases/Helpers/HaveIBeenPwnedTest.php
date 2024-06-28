<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration\Cases\Helpers;

use BlueChip\Security\Helpers\HaveIBeenPwned;
use BlueChip\Security\Tests\Integration\Constants;
use BlueChip\Security\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test integration with Pwned Passwords API.
 *
 * @internal These tests result in external requests to https://api.pwnedpasswords.com being made!
 */
final class HaveIBeenPwnedTest extends TestCase
{
    /**
     * Test that pwned password is reported as such.
     */
    #[Group('external')]
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
     */
    #[Group('external')]
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
