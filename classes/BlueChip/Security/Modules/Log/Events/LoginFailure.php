<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class LoginFailure extends Event
{
    /**
     * @var string Static event identificator.
     */
    const ID = 'login_failure';

    /**
     * @var string Event log level.
     */
    const LOG_LEVEL = \Psr\Log\LogLevel::NOTICE;

    /**
     * __('Username')
     *
     * @var string Username used in failed login attempt.
     */
    protected $username = '';


    public function getName(): string
    {
        return __('Failed login', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Login attempt with username {username} failed.', 'bc-security');
    }


    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
