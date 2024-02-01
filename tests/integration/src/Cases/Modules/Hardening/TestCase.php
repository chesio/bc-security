<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Settings;
use BlueChip\Security\Tests\Integration\TestCase as IntegrationTestCase;

abstract class TestCase extends IntegrationTestCase
{
    /**
     * @var string
     */
    protected const DUMMY_USER_EMAIL = 'dummy@example.com';

    /**
     * @var string
     */
    protected const DUMMY_USER_LOGIN = 'dummy';

    /**
     * @var int
     */
    protected $dummy_user_id;


    protected function prepareTest(): void
    {
        // Create dummy user object.
        $this->dummy_user_id = $this->factory->user->create([
            'user_email' => self::DUMMY_USER_EMAIL,
            'user_login' => self::DUMMY_USER_LOGIN,
        ]);
    }


    /**
     * @param bool $active Set to true to get settings with all hardening options on and vice versa for false.
     */
    protected function setHardening(bool $active): void
    {
        $settings = (new Settings())->forHardening();

        $settings->set(\array_fill_keys(\array_keys($settings->get()), $active));
    }


    /**
     * Set up $_POST data necessary to test via \edit_user() function.
     *
     * @param string $password
     */
    protected function setUpUserPostData(string $password): void
    {
        // To be able to test \edit_user() method.
        $_POST['nickname'] = 'John Doe';
        $_POST['email'] = 'john@doe.com';
        $_POST['pass1'] = $password;
        $_POST['pass2'] = $password;
    }


    /**
     * Clean up $_POST data set in setUpUserPostData() method.
     */
    protected function tearDownUserPostData(): void
    {
        unset($_POST['nickname']);
        unset($_POST['email']);
        unset($_POST['pass1']);
        unset($_POST['pass2']);
    }
}
