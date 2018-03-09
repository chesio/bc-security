<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class LoginFailure extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::LOGIN_FAILURE,
            __('Failed login', 'bc-security'),
            LogLevel::NOTICE,
            __('Login attempt with username {username} failed.', 'bc-security'),
            ['username' => __('Username', 'bc-security')]
        );
    }
}
