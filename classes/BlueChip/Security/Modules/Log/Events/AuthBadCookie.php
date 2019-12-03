<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

class AuthBadCookie extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'auth_bad_cookie';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::NOTICE;

    /**
     * __('Username')
     *
     * @var string Username used in authentication with a bad cookie.
     */
    protected $username = '';


    public function getName(): string
    {
        return __('Bad authentication cookie', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Bad authentication cookie used with {username}.', 'bc-security');
    }


    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
