<?php

namespace BlueChip\Security\Tests\Integration\Cases\Modules\Hardening;

use BlueChip\Security\Modules\Hardening;
use BlueChip\Security\Settings;
use BlueChip\Security\Tests\Integration\TestCase as IntegrationTestCase;

abstract class TestCase extends IntegrationTestCase
{
    /**
     * @param bool $active Set to true to get settings with all hardening options on and vice versa for false.
     *
     * @return Hardening\Settings Settings object for hardening module with all options either on or off.
     */
    protected function getSettings(bool $active): Hardening\Settings
    {
        $settings = (new Settings())->forHardening();

        foreach ($settings as $name => $value) {
            $settings[$name] = $active;
        }

        return $settings;
    }


    /**
     * Set up $_POST data necessary to test via \edit_user() function.
     *
     * @param string $password
     */
    protected function setUpUserPostData(string $password)
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
    protected function tearDownUserPostData()
    {
        unset($_POST['nickname']);
        unset($_POST['email']);
        unset($_POST['pass1']);
        unset($_POST['pass2']);
    }
}
