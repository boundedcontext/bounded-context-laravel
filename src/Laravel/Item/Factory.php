<?php

namespace BoundedContext\Laravel\Item;

use BoundedContext\Contracts\Core\Collectable;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Contracts\Generator\Identifier as IdentifierGenerator;
use BoundedContext\Laravel\ValueObject\Uuid;
use BoundedContext\Log\Item;
use BoundedContext\Map\Map;
use BoundedContext\Schema\Schema;
use BoundedContext\ValueObject\DateTime;
use BoundedContext\ValueObject\Integer;

class Factory
{
    private $class_map;
    private $generator;

    public function __construct(Map $class_map, IdentifierGenerator $generator)
    {
        $this->class_map = $class_map;
        $this->generator = $generator;
    }

    public function existing(Identifier $id, Identifier $type_id, DateTime $occurred_at, Schema $schema, Integer $version)
    {
        $upgrader = $this->upgrader_factory->get($schema, $version);
        $upgrader->run();

        return new Item(
            $id,
            $type_id,
            $occurred_at,
            $upgraded_serialized_class->version(),
            $class::deserialize($upgrader->schema())
        );
    }

    public function deserialize($serialized_item)
    {
        $type_id = new Uuid($serialized_item['type_id']);

        $class = $this->class_map->get_class($type_id);
        $upgrader_class = $this->get_upgrader_class($type_id);

        $upgraded_serialized_class = new $upgrader_class(
            new Schema($serialized_item['payload']),
            new Integer($serialized_item['version'])
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
            new Integer()
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
}
