<?php

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Login;

use BlueChip\Security\Modules\Login;
use BlueChip\Security\Settings;
use BlueChip\Security\Tests\Integration\Constants;
use BlueChip\Security\Tests\Integration\TestCase;

class GatekeeperTest extends TestCase
{
    /**
     * @internal WP test suite already includes user with username `admin`, so there is no need to explicitly create it.
     *
     * @var string Existing username that is on username blacklist.
     */
    private const ADMIN = 'admin';

    /**
     * @var string Non-existing username that is on username blacklist.
     */
    private const ADMINISTRATOR = 'administrator';

    /**
     * @var string Existing username that is not on username blacklist.
     */
    private const CHESIO = 'chesio';


    /**
     * Setup username blacklist before every test.
     */
    public function prepareTest(): void
    {
        // Add `admin` via settings.
        (new Settings())->forLogin()->update(Login\Settings::USERNAME_BLACKLIST, [self::ADMIN,]);

        // Add `administrator` via filter - this way the filter is tested as well.
        \add_filter(
            Login\Hooks::USERNAME_BLACKLIST,
            function (array $usernames): array {
                return \array_merge($usernames, [self::ADMINISTRATOR]);
            }
        );

        // Create additional user.
        $this->factory->user->create(['user_login' => self::CHESIO]);
    }


    public function testExistingBlacklistedUsernameIsNotLocked()
    {
        // Ensure that login with existing username succeeds even if blacklisted.
        $this->authenticateShouldPass(self::ADMIN, Constants::FACTORY_PASSWORD);
    }


    public function testNonExistingBlacklistedUsernameIsLocked()
    {
        // Ensure that login with non-existing username gets blocked when if blacklisted.
        $this->authenticateShouldDie(self::ADMINISTRATOR, Constants::FACTORY_PASSWORD);
    }


    public function testExistingNonBlacklistedUsernameIsNotLocked()
    {
        // Ensure that login with existing username succeeds when not blacklisted.
        $this->authenticateShouldPass(self::CHESIO, Constants::FACTORY_PASSWORD);
    }


    public function testAccessLock()
    {
        // Login with non-existing blacklisted username should die and get our IP locked ...
        $this->authenticateShouldDie(self::ADMINISTRATOR, Constants::FACTORY_PASSWORD);

        // ... therefore any successive logins should die as well, even with username that is not on blacklist.
        $this->authenticateShouldDie(self::ADMIN, Constants::FACTORY_PASSWORD);
        $this->authenticateShouldDie(self::CHESIO, Constants::FACTORY_PASSWORD);
    }


    /**
     * Pass $username and $password to \wp_authenticate() and expect it to die.
     *
     * @param string $username
     * @param string $password
     */
    private function authenticateShouldDie(string $username, string $password): void
    {
        $exception = null;
        try {
            \wp_authenticate($username, $password);
        } catch (\WPDieException $exception) {
            $this->assertSame(503, $exception->getCode());
        }
        $this->assertInstanceOf(\WPDieException::class, $exception);
    }


    /**
     * Pass $username and $password to \wp_authenticate() and expect it succeed.
     *
     * @param string $username
     * @param string $password
     */
    private function authenticateShouldPass(string $username, string $password): void
    {
        $this->assertInstanceOf(\WP_User::class, \wp_authenticate($username, $password));
    }
}
