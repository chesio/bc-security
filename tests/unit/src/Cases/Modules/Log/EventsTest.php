<?php

namespace BlueChip\Security\Tests\Unit\Cases\Modules\Log;

use BlueChip\Security\Modules\Log;

class EventsTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    /**
     * Ensure that every event type (class) has the necessary constants: ID and LEVEL.
     */
    public function testConstants()
    {
        $event_classes = Log\EventsManager::getMapping();

        foreach ($event_classes as $event_class) {
            $reflection = new \ReflectionClass($event_class);

            $this->assertIsString($reflection->getConstant('ID'));
            $this->assertIsString($reflection->getConstant('LOG_LEVEL'));
        }
    }


    /**
     * Ensure that only PSR-4 compliant log levels are reported by events.
     */
    public function testLogLevels()
    {
        $reflection = new \ReflectionClass(\Psr\Log\LogLevel::class);
        $log_levels = $reflection->getConstants();

        $event_instances = Log\EventsManager::getInstances();
        foreach ($event_instances as $event) {
            $this->assertContains($event->getLogLevel(), $log_levels);
        }
    }


    /**
     * Ensure that event log message references all event context data.
     */
    public function testLogMessages()
    {
        $event_instances = Log\EventsManager::getInstances();
        foreach ($event_instances as $event) {
            $context_data = $event->getContext();
            $message = $event->getMessage();

            foreach (array_keys($context_data) as $key) {
                $this->assertContains("{{$key}}", $message);
            }
        }
    }


    /**
     * Ensure that there are no context items without label.
     */
    public function testContextLabels()
    {
        $event_instances = Log\EventsManager::getInstances();
        foreach ($event_instances as $event) {
            $context_labels = $event->explainContext();
            foreach ($context_labels as $key => $label) {
                $this->assertNotEmpty($label, "Context item '{$key}' of '{$event->getName()}' event has an empty label!");
            }
        }
    }
}
