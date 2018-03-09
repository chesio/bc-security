<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

/**
 * Every event must be constructed using event ID.
 */
abstract class EventsManager
{
    /**
     * Create event object for given $id.
     *
     * @param string $id Valid event ID.
     * @return \BlueChip\Security\Modules\Log\Event|null
     */
    public static function create(string $id)
    {
        switch ($id) {
            case Event::AUTH_BAD_COOKIE:
                return new Events\AuthBadCookie();
            case Event::LOGIN_FAILURE:
                return new Events\LoginFailure();
            case Event::LOGIN_LOCKOUT:
                return new Events\LoginLockout();
            case Event::LOGIN_SUCCESSFUL:
                return new Events\LoginSuccessful();
            case Event::QUERY_404:
                return new Events\Query404();
            case Event::CORE_CHECKSUMS_VERIFICATION_ALERT:
                return new Events\CoreChecksumsVerificationAlert();
            case Event::PLUGIN_CHECKSUMS_VERIFICATION_ALERT:
                return new Events\PluginChecksumsVerificationAlert();
            default:
                return null;
        }
    }


    /**
     * Return a list of all events.
     *
     * @return array
     */
    public static function enlist(): array
    {
        return [
            Event::AUTH_BAD_COOKIE,
            Event::LOGIN_FAILURE,
            Event::LOGIN_SUCCESSFUL,
            Event::LOGIN_LOCKOUT,
            Event::QUERY_404,
            Event::CORE_CHECKSUMS_VERIFICATION_ALERT,
            Event::PLUGIN_CHECKSUMS_VERIFICATION_ALERT,
        ];
    }
}
