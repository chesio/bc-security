<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Events;

/**
 * Log channels for monitoring
 */
interface EventType
{
    const AUTH_BAD_COOKIE = 'auth_bad_cookie';
    const LOGIN_FAILURE = 'login_failure';
    const LOGIN_LOCKOUT = 'login_lockdown';
    const LOGIN_SUCCESSFUL = 'login_success';
    const QUERY_404 = 'query_404';
}
