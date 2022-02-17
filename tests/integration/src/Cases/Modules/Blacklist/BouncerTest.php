<?php

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Modules\IpBlacklist;
use BlueChip\Security\Tests\Integration\TestCase;

class BouncerTest extends TestCase
{
    /**
     * Ensure that blocking access results in wp_die() being called with proper response code.
     */
    public function testBlockAccessTemporarily()
    {
        try {
            IpBlacklist\Bouncer::blockAccessTemporarily();
        } catch (\WPDieException $exception) {
            $this->assertSame(503, $exception->getCode());
        }
    }
}
