<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration\Cases\Helpers;

use BlueChip\Security\Helpers\Utils;
use BlueChip\Security\Tests\Integration\TestCase;

final class UtilsTest extends TestCase
{
    /**
     * Ensure that blocking access results in wp_die() being called with proper response code.
     */
    public function testBlockAccessTemporarily()
    {
        $exception = null;

        try {
            Utils::blockAccessTemporarily();
        } catch (\WPDieException $exception) {
            $this->assertSame(503, $exception->getCode());
        }

        $this->assertInstanceOf(\WPDieException::class, $exception);
    }
}
