<?php namespace BoundedContext\Laravel\Event\Snapshot;

use BoundedContext\Contracts\Event\Snapshot\Snapshot as EventSnapshotContract;
use BoundedContext\Event\Snapshot\Snapshot;
use BoundedContext\Map\Map;

class Upgrader implements \BoundedContext\Contracts\Event\Snapshot\Upgrader
{
    private $event_map;

    public function __construct(Map $event_map)
    {
        $this->event_map = $event_map;
    }

    public function snapshot(EventSnapshotContract $snapshot)
    {
        $event_class = $this->event_map->get_class(
            $snapshot->type_id()
        );

        $upgrader_class = preg_replace(
            array('/Command/', '/Event/'),
            array('Upgrader\\Command', 'Upgrader\\Event'),
            $event_class
        );

        $upgrader = new $upgrader_class(
            $snapshot->schema(),
            $snapshot->version()
        );

        $upgrader->run();

        return new Snapshot(
            $snapshot->id(),
            $upgrader->version(),
            $snapshot->occurred_at(),
            $snapshot->type_id(),
            $upgrader->schema()
        );
    }
}
