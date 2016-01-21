<?php namespace BoundedContext\Laravel\Event\Version;

use BoundedContext\Contracts\Event\Event;
use BoundedContext\Contracts\Event\Version\Factory as EventVersionFactory;
use BoundedContext\Contracts\Generator\DateTime;
use BoundedContext\Contracts\Generator\Identifier;
use BoundedContext\Map\Map;
use BoundedContext\ValueObject\Integer;

class Factory implements \BoundedContext\Contracts\Event\Version\Factory
{
    protected $identifier_generator;
    protected $datetime_generator;
    protected $event_map;

    public function __construct(
        Identifier $identifier_generator,
        DateTime $datetime_generator,
        Map $event_map
    )
    {
        $this->identifier_generator = $identifier_generator;
        $this->datetime_generator = $datetime_generator;
        $this->event_map = $event_map;
    }

    public function event(Event $event)
    {
        $upgrader = $this->upgrader_factory->event($event);

        return $upgrader->version();
    }
}
