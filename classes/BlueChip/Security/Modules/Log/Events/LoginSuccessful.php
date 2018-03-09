<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class LoginSuccessful extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::LOGIN_SUCCESSFUL,
            __('Successful login', 'bc-security'),
            LogLevel::INFO,
            __('User {username} logged in successfully.', 'bc-security'),
            ['username' => __('Username', 'bc-security')]
        );
    }
}
