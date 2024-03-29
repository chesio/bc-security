<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use WP_Error;

class LoginFailure extends Event
{
    /**
     * @var string Static event identificator.
     */
    public const ID = 'login_failure';

    /**
     * @var string Event log level.
     */
    protected const LOG_LEVEL = \Psr\Log\LogLevel::NOTICE;

    /**
     * __('Username')
     *
     * @var string Username used in failed login attempt.
     */
    protected string $username = '';

    /**
     * __('Error code')
     *
     * @var string Reason why login failed as error code.
     */
    protected string $error_code = '';

    /**
     * __('Error message')
     *
     * @var string Reason why login failed as human-readable message.
     */
    protected string $error_message = '';


    public function getName(): string
    {
        return __('Failed login', 'bc-security');
    }


    public function getMessage(): string
    {
        return __('Login attempt with username {username} failed.', 'bc-security');
    }


    /**
     * Set reason why login attempt failed.
     */
    public function setError(WP_Error $error): self
    {
        $this->error_code = (string) $error->get_error_code();
        $this->error_message = $error->get_error_message();
        return $this;
    }


    /**
     * Set username used in failed login attempt (if any).
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
