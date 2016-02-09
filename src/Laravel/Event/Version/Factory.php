<?php namespace BoundedContext\Laravel\Event\Version;

use BoundedContext\Contracts\Event\Event;
use BoundedContext\Contracts\Generator\DateTime;
use BoundedContext\Contracts\Generator\Identifier;
use BoundedContext\Map\Map;
use BoundedContext\Schema\Schema;
use BoundedContext\ValueObject\Integer as Integer_;

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
        $event_class = get_class($event);

        $upgrader_class = preg_replace(
            array('/Command/', '/Event/'),
            array('Upgrader\\Command', 'Upgrader\\Event'),
            $event_class
        );

        $upgrader = new $upgrader_class(new Schema(), new Integer_());

        return $upgrader->latest_version();
    }
}
