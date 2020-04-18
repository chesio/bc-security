<?php

namespace BlueChip\Security\Tests\Unit\Cases\Modules\Log;

use Brain\Monkey\Actions;
use BlueChip\Security\Modules\Log;

class EventsMonitorTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    /**
     * @var \BlueChip\Security\Modules\Log\EventsMonitor
     */
    private $monitor;


    protected function setUp()
    {
        parent::setUp();

        $this->monitor = new Log\EventsMonitor('1.2.3.4', '5.6.7.8');
    }


    public function testBadCookie()
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\AuthBadCookie::class));

        $this->monitor->logBadCookie(['username' => 'test-user']);
    }


    public function testFailedLogin()
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\LoginFailure::class));

        $this->monitor->logFailedLogin('test-user');
    }


    public function testLoginLockout()
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\LoginLockout::class));

        $this->monitor->logLockoutEvent('4.3.2.1', 'test-user', 600);
    }


    public function testSuccessfulLogin()
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\LoginSuccessful::class));

        $this->monitor->logSuccessfulLogin('test-user');
    }
}
