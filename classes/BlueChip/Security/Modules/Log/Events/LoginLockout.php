<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class LoginLockout extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'login_lockdown';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::WARNING;

    /**
     * __('Duration')
     *
     * @var int Lockout duration (in seconds).
     */
    protected int $duration = 0;

    /**
     * __('IP Address')
     *
     * @var string Remote IP address.
     */
    protected string $ip_address = '';

    /**
     * __('Username')
     *
     * @var string Username used in failed login attempt.
     */
    protected string $username = '';


    public function getName(): string
    {
        return __('Login lockout', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Remote IP address {ip_address} has been locked out from login for {duration} seconds. Last username used for login was {username}.', 'bc-security');
    }


    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }


    public function setIpAddress(string $ip_address): self
    {
        $this->ip_address = $ip_address;
        return $this;
    }


    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
