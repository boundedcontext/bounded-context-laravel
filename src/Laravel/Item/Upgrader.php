<?php

namespace BoundedContext\Laravel\Item;

use BoundedContext\Collection\Collectable;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Contracts\Generator\Identifier as IdentifierGenerator;
use BoundedContext\Laravel\ValueObject\Uuid;
use BoundedContext\Log\Item;
use BoundedContext\Map\Map;
use BoundedContext\Schema\Schema;
use BoundedContext\ValueObject\DateTime;
use BoundedContext\ValueObject\Version;

class Upgrader
{
    private $class_map;
    private $generator;

    public function __construct(Map $class_map, IdentifierGenerator $generator)
    {
        $this->class_map = $class_map;
        $this->generator = $generator;
    }

    private function get_upgrader_class(Identifier $type_id)
    {
        $class = $this->class_map->get_class($type_id);

        $aggregate_prefix = substr($class, 0, strrpos($class, "\\"));
        $upgrader_suffix = substr($class, strrpos($class, "\\"));
        $upgrader_class = $aggregate_prefix . '\\Upgrader' . $upgrader_suffix;

        return $upgrader_class;
    }

    public function deserialize($serialized_item)
    {
        $type_id = new Uuid($serialized_item['type_id']);

        $class = $this->class_map->get_class($type_id);
        $upgrader_class = $this->get_upgrader_class($type_id);

        $upgraded_serialized_class = new $upgrader_class(
            new Schema($serialized_item['payload']),
            new Version($serialized_item['version'])
        );

        $upgraded_serialized_class->run();

        return new Item(
            new Uuid($serialized_item['id']),
            new Uuid($serialized_item['type_id']),
            new DateTime($serialized_item['occurred_at']),
            $upgraded_serialized_class->version(),
            $class::deserialize($upgraded_serialized_class->schema())
        );
    }

    public function generate(Collectable $payload)
    {
        $type_id = $this->class_map->get_id($payload);
        $event_class = get_class($payload);

        $upgrader_class = $this->get_upgrader_class($type_id);

        $upgrader = new $upgrader_class(
            new Schema(),
            new Version()
        );

        $upgrader->run();

        return new Item(
            $this->generator->generate(),
            $type_id,
            new DateTime,
            $upgrader->version(),
            $payload
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
