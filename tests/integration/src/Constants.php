<?php

namespace BlueChip\Security\Tests\Integration;

/**
 * Various constants used through-out integration tests.
 */
abstract class Constants
{
    /**
     * @var string Password that definitely have been pwned.
     */
    const PWNED_PASSWORD = '123456';

    /**
     * @var string Password that (hopefully) have not been pwned yet.
     */
    const SAFE_PASSWORD = 'This password have not been pwned ... yet.';
}
