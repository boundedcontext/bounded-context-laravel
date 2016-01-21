<?php namespace BoundedContext\Laravel\Event\Snapshot;

use BoundedContext\Contracts\Event\Snapshot\Snapshot;
use BoundedContext\Map\Map;

class Upgrader implements \BoundedContext\Contracts\Event\Snapshot\Upgrader
{
    private $event_map;

    public function __construct(
        Map $event_map
    )
    {
        $this->event_map = $event_map;
    }

    public function snapshot(Snapshot $snapshot)
    {
        $upgrader = $this->schema_upgrader_factory->schema(
            $snapshot->schema(),
            $snapshot->version()
        );

        $snapshot->increment($schema);
    }
}
