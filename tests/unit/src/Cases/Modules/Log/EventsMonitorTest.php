<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Modules\Log;

use Brain\Monkey\Actions;
use BlueChip\Security\Modules\Log;
use BlueChip\Security\Modules\Log\EventsMonitor;
use BlueChip\Security\Tests\Unit\TestCase;

final class EventsMonitorTest extends TestCase
{
    private EventsMonitor $monitor;


    protected function setUp(): void
    {
        parent::setUp();

        $this->monitor = new Log\EventsMonitor('1.2.3.4', '5.6.7.8');
    }


    public function testBadCookie(): void
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\AuthBadCookie::class));

        $this->runUnaccessibleMethod($this->monitor, 'logBadCookie', ['username' => 'test-user']);
    }


    public function testFailedLogin(): void
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\LoginFailure::class));
        $wp_error = \Mockery::mock(\WP_Error::class);
        $wp_error->allows([
            'get_error_code' => 'test-error-code',
            'get_error_message' => 'Test error message.',
        ]);

        $this->runUnaccessibleMethod($this->monitor, 'logFailedLogin', 'test-user', $wp_error);
    }


    public function testLoginLockout(): void
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\LoginLockout::class));

        $this->runUnaccessibleMethod($this->monitor, 'logLockoutEvent', '4.3.2.1', 'test-user', 600);
    }


    public function testSuccessfulLogin(): void
    {
        Actions\expectDone(Log\Action::EVENT)->once()->with(\Mockery::type(Log\Events\LoginSuccessful::class));

        $this->runUnaccessibleMethod($this->monitor, 'logSuccessfulLogin', 'test-user');
    }
}
