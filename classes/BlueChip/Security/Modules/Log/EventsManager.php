<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Log;

/**
 * Events manager helps to maintain event ID => event class/instance mapping.
 */
abstract class EventsManager
{
    private static $mapping = [
        Events\AuthBadCookie::ID => Events\AuthBadCookie::class,
        Events\LoginFailure::ID => Events\LoginFailure::class,
        Events\LoginLockout::ID => Events\LoginLockout::class,
        Events\LoginSuccessful::ID => Events\LoginSuccessful::class,
        Events\Query404::ID => Events\Query404::class,
    ];


    /**
     * Create event object for given $id.
     *
     * @param string $event_id Valid event ID.
     * @return \BlueChip\Security\Modules\Log\Event|null
     */
    public static function create(string $event_id)
    {
        $classname = self::$mapping[$event_id] ?? '';
        return $classname ? new $classname() : null;
    }


    /**
     * Return list of event classes indexed by their IDs.
     *
     * @return array
     */
    public static function getMapping(): array
    {
        return self::$mapping;
    }


    /**
     * Return list of event instances indexed by their IDs.
     *
     * @return \BlueChip\Security\Modules\Log\Event[]
     */
    public static function getInstances(): array
    {
        return array_map(
            function (string $classname): Event {
                return new $classname();
            },
            self::$mapping
        );
    }
}
