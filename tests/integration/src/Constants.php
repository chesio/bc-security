<?php

namespace BlueChip\Security\Tests\Integration;

/**
 * Various constants used through-out integration tests.
 */
abstract class Constants
{
    /**
     * @var string Username of mocked WP_User instance created automatically by WordPress test suite.
     */
    public const DEFAULT_USERNAME = 'admin';

    /**
     * @var string Password assigned to mocked WP_User instances by WordPress test suite.
     */
    public const FACTORY_PASSWORD = 'password';

    /**
     * @var string Password that definitely have been pwned.
     */
    public const PWNED_PASSWORD = '123456';

    /**
     * @var string Password that (hopefully) have not been pwned yet.
     */
    public const SAFE_PASSWORD = 'This password have not been pwned ... yet.';

    /**
     * @var string IP address that is part of Amazon Web Services cloud (us-west-1)
     */
    public const AMAZON_WEB_SERVICE_IP_ADDRESS = '52.93.178.234';

    /**
     * @var string IP address that is definitely not part of Amazon Web Services cloud.
     */
    public const CLOUDFLARE_DNS_IP_ADDRESS = '1.1.1.1';
}
