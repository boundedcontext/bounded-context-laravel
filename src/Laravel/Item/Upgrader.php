<?php

namespace BoundedContext\Laravel\Item;

use BoundedContext\Contracts\Event;
use BoundedContext\Contracts\Map;
use BoundedContext\Log\Item;
use BoundedContext\Schema\Schema;
use BoundedContext\ValueObject\DateTime;
use BoundedContext\ValueObject\Uuid;
use BoundedContext\ValueObject\Version;

class Upgrader
{
    private $event_map;

    public function __construct(Map $event_map)
    {
        $this->event_map = $event_map;
    }

    private function get_event_class(Uuid $type_id)
    {
        return $this->event_map->get_event_class($type_id);
    }

    private function get_upgrader_class(Uuid $type_id)
    {
        $event_class = $this->get_event_class($type_id);

        $aggregate_prefix = substr($event_class, 0, strpos($event_class, "Event"));
        $upgrader_suffix = explode('Event\\', $event_class)[1];
        $upgrader_class = $aggregate_prefix . 'Upgrader\\' . $upgrader_suffix;

        return $upgrader_class;
    }

    public function deserialize($serialized_item)
    {
        $type_id = new Uuid($serialized_item['type_id']);

        $event_class = $this->get_event_class($type_id);
        $upgrader_class = $this->get_upgrader_class($type_id);

        $upgraded_serialized_event = new $upgrader_class(
            new Schema($serialized_item['event']),
            new Version($serialized_item['version'])
        );

        $upgraded_serialized_event->run();

        return new Item(
            new Uuid($serialized_item['id']),
            new Uuid($serialized_item['type_id']),
            new DateTime($serialized_item['occurred_at']),
            $upgraded_serialized_event->version(),
            $event_class::deserialize($upgraded_serialized_event->schema())
        );
    }

    public function generate(Event $event)
    {
        $type_id = $this->event_map->get_id($event);
        $event_class = get_class($event);

        $upgrader_class = $this->get_upgrader_class($type_id);

        $upgrader = new $upgrader_class(
            new Schema(),
            new Version()
        );

        $upgrader->run();

        return new Item(
            Uuid::generate(),
            $type_id,
            new DateTime,
            $upgrader->version(),
            $event
        );
    }

    public function get_latest_version(Uuid $type_id)
    {
        $upgrader_class = $this->get_upgrader_class($type_id);

        $upgrader = new $upgrader_class(
            new Schema(),
            new Version()
        );

        $upgrader->run();

        return $upgrader->version();
    }
}
