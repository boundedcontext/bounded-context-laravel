<?php namespace BoundedContext\Laravel\Event\Snapshot;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Event\Event;
use BoundedContext\Event\Snapshot\Snapshot;
use BoundedContext\Contracts\Event\Version\Factory as EventVersionFactory;
use BoundedContext\Contracts\Generator\DateTime;
use BoundedContext\Contracts\Generator\Identifier;
use BoundedContext\Schema\Schema;
use BoundedContext\Contracts\Schema\Schema as SchemaContract;
use BoundedContext\Map\Map;
use BoundedContext\ValueObject\Integer;

class Factory implements \BoundedContext\Contracts\Event\Snapshot\Factory
{
    protected $identifier_generator;
    protected $datetime_generator;
    protected $event_version_factory;
    protected $event_map;

    public function __construct(
        Identifier $identifier_generator,
        DateTime $datetime_generator,
        EventVersionFactory $event_version_factory,
        Map $event_map
    )
    {
        $this->identifier_generator = $identifier_generator;
        $this->datetime_generator = $datetime_generator;
        $this->event_version_factory = $event_version_factory;
        $this->event_map = $event_map;
    }

    public function event(Event $event)
    {
        return new Snapshot(
            $this->identifier_generator->generate(),
            $this->event_version_factory->event($event),
            $this->datetime_generator->now(),
            $this->event_map->get_id($event),
            new Schema($event->serialize())
        );
    }

    public function collection(Collection $events)
    {
        $event_snapshots = new \BoundedContext\Collection\Collection();

        foreach($events as $event)
        {
            $event_snapshots->append(
                $this->event($event)
            );
        }

        return $event_snapshots;
    }

    public function schema(SchemaContract $schema)
    {
        return new Snapshot(
            $this->identifier_generator->string($schema->id),
            new Integer($schema->version),
            $this->datetime_generator->string($schema->occurred_at),
            $this->identifier_generator->string($schema->type_id),
            new Schema($schema->event)
        );
    }
}
