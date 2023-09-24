<?php

declare(strict_types=1);

namespace BlueChip\Security\Tests\Unit\Cases\Modules\Log;

use BlueChip\Security\Modules\Log;

class EventsManagerTest extends \BlueChip\Security\Tests\Unit\TestCase
{
    /**
     * Ensure that mapping from event ID to event class is sane.
     */
    public function testMapping(): void
    {
        $events = Log\EventsManager::getMapping();

        foreach ($events as $event_id => $event_class) {
            $reflection = new \ReflectionClass($event_class);
            $this->assertEquals($reflection->getConstant('ID'), $event_id);
        }
    }
}
