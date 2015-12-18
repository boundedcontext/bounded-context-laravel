<?php namespace BoundedContext\Laravel\Upgrader;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Contracts\Generator\Identifier as IdentifierGenerator;
use BoundedContext\Map\Map;

class Factory
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
}
