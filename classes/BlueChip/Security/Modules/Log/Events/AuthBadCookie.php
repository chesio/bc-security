<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log\Events;

use BlueChip\Security\Modules\Log\Event;
use Psr\Log\LogLevel;

class AuthBadCookie extends Event
{
    public function __construct()
    {
        parent::__construct(
            self::AUTH_BAD_COOKIE,
            __('Bad authentication cookie', 'bc-security'),
            LogLevel::NOTICE,
            __('Bad authentication cookie used with {username}.', 'bc-security'),
            ['username' => __('Username', 'bc-security')]
        );
    }
}
