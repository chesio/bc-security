<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class LoginSuccessful extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'login_success';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::INFO;

    /**
     * __('Username')
     *
     * @var string Username of user who logged in.
     */
    protected $username = '';


    public function getName(): string
    {
        return __('Successful login', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('User {username} logged in successfully.', 'bc-security');
    }


    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
