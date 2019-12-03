<?php

namespace BlueChip\Security\Modules\Log;

/**
 * Base class for events. Implement event types as subclasses of this class.
 */
abstract class Event
{
    /**
     * @var string Static event identificator.
     */
    const ID = '';

    /**
     * @var string Log level.
     */
    const LOG_LEVEL = '';

    /**
     * @internal Static identifier is used for event type identification wherever use of classname is a bit cumbersome
     * like in database data or GET requests.
     *
     * @return string Static identifier for event type (class).
     */
    public function getId(): string
    {
        return static::ID;
    }


    /**
     * @return string Log level for event type (class).
     */
    public function getLogLevel(): string
    {
        return static::LOG_LEVEL;
    }


    /**
     * @return string Human readable name unique for event type (class).
     */
    abstract public function getName(): string;


    /**
     * @return string Log message providing context to the event.
     */
    abstract public function getMessage(): string;


    /**
     * @return array Context data for this event.
     */
    public function getContext(): array
    {
        $reflection = new \ReflectionClass(static::class);

        $output = [];
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            $output[$property->getName()] = $this->{$property->getName()};
        }

        return $output;
    }


    /**
     * @return array Context columns with human readable descriptions (labels).
     */
    public function explainContext(): array
    {
        $reflection = new \ReflectionClass(static::class);

        $output = [];
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            $output[$property->getName()] = self::getPropertyLabel($property);
        }

        return $output;
    }


    /**
     * Extract context property label.
     *
     * @internal Property label must be enclosed in pseudo-call to translation function __('I am the label') placed in
     * property PHPDoc comment.
     *
     * @param \ReflectionProperty $property
     * @return string
     */
    private static function getPropertyLabel(\ReflectionProperty $property): string
    {
        $matches = [];
        if (\preg_match("/__\('(.+)'\)/i", $property->getDocComment(), $matches)) {
            return __($matches[1], 'bc-security');
        } else {
            return '';
        }
    }
}
