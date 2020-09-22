<?php

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;

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
    protected $username = '';

    /**
     * __('Error code')
     *
     * @var string Reason why login failed as error code.
     */
    protected $error_code = '';

    /**
     * __('Error message')
     *
     * @var string Reason why login failed as human-readable message.
     */
    protected $error_message = '';


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
     *
     * @param \WP_Error $error
     *
     * @return self
     */
    public function setError(\WP_Error $error): self
    {
        $this->error_code = $error->get_error_code();
        $this->error_message = $error->get_error_message();
        return $this;
    }


    /**
     * Set username used in failed login attempt (if any).
     *
     * @param string $username
     *
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
}
