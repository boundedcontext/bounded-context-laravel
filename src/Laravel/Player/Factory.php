<?php namespace BoundedContext\Laravel\Player;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Contracts\Generator\Identifier as IdentifierGenerator;
use BoundedContext\Player\Snapshot;
use Illuminate\Contracts\Foundation\Application;

class Factory
{
    private $app;
    private $generator;

    public function __construct(Application $app, IdentifierGenerator $generator)
    {
        $this->app = $app;
        $this->generator = $generator;
    }

    public function id(Identifier $namespace, Snapshot $snapshot)
    {
        $args = [];

        $class = $namespace->serialize();

        $reflection = new \ReflectionClass($class);

        $properties = $reflection->getProperties();
        foreach($properties as $property)
        {
            $property_name = $property->getName();
            $property_class = $property->class;

            if($property_class === Snapshot::class)
            {
                $args[$property_name] = $snapshot;
            } else
            {
                $args[$property_name] = $this->app->make($property_class);
            }
        }

        return $reflection->newInstanceArgs($args);
    }
}
