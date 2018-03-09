<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class LoginLockout extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::LOGIN_LOCKOUT,
            __('Login lockout', 'bc-security'),
            LogLevel::WARNING,
            __('Remote IP address {ip_address} has been locked out from login for {duration} seconds. Last username used for login was {username}.', 'bc-security'),
            ['ip_address' => __('IP Address', 'bc-security'), 'username' => __('Username', 'bc-security'), 'duration' => __('Duration', 'bc-security')]
        );
    }
}
