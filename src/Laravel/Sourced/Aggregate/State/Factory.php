<?php namespace BoundedContext\Laravel\Sourced\Aggregate\State;

use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Snapshot;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\State\Factory
{
    protected $state_class;
    protected $state_projection_class;

    public function with(Command $command)
    {
        $command_class = get_class($command);

        $aggregate_prefix = substr($command_class, 0, strpos($command_class, "Command"));

        $this->state_class = $aggregate_prefix . 'State';
        $this->state_projection_class = $aggregate_prefix . 'Projection';

        return $this;
    }

    public function snapshot(Snapshot $snapshot)
    {
        $projection_class = new \ReflectionClass($this->state_projection_class);

        $projection = $projection_class->newInstanceArgs();

        $schema = $snapshot->schema();
        if($schema->serialize() == [])
        {
            return new $this->state_class(
                $snapshot->id(),
                $snapshot->version(),
                $projection
            );
        }

        $projection_object = new \ReflectionObject(
            $projection
        );

        $properties = $projection_object->getProperties();
        foreach ($properties as $property)
        {

            dd($property->getDocComment());
            //$property->setValue($projection_object, )
        }


        dd($projection);

        dd($properties);
        dd($snapshot);
    }
}
