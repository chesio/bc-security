<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Log;

/**
 * Events manager helps to maintain event ID => event class/instance mapping.
 */
abstract class EventsManager
{
    /**
     * @var array<string,string>
     */
    private static $mapping = [
        Events\AuthBadCookie::ID => Events\AuthBadCookie::class,
        Events\BadRequestBan::ID => Events\BadRequestBan::class,
        Events\BlocklistHit::ID => Events\BlocklistHit::class,
        Events\LoginFailure::ID => Events\LoginFailure::class,
        Events\LoginLockout::ID => Events\LoginLockout::class,
        Events\LoginSuccessful::ID => Events\LoginSuccessful::class,
        Events\Query404::ID => Events\Query404::class,
    ];


    /**
     * Create event object for given $id.
     *
     * @param string $event_id Valid event ID.
     *
     * @return \BlueChip\Security\Modules\Log\Event|null
     */
    public static function create(string $event_id): ?Event
    {
        $classname = self::$mapping[$event_id] ?? '';
        return $classname ? new $classname() : null;
    }


    /**
     * Return list of event classes indexed by their IDs.
     *
     * @return array<string,string>
     */
    public static function getMapping(): array
    {
        return self::$mapping;
    }


    /**
     * Return list of event instances indexed by their IDs.
     *
     * @return array<string,Event>
     */
    public static function getInstances(): array
    {
        return \array_map(
            fn (string $classname): Event => new $classname(),
            self::$mapping
        );
    }
}
