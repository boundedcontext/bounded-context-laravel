<?php namespace BoundedContext\Laravel\Command\Handler;

use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Repository\Repository;
use BoundedContext\ValueObject\Uuid;
use Illuminate\Contracts\Foundation\Application;

class Factory
{
    protected $app;

    public function __construct(Application $app, Command $command)
    {
        $this->app = $app;
        $this->command = $command;
    }

    public function generate()
    {
        $command_class = get_class($this->command);

        $aggregate_namespace = substr($command_class, 0, strpos($command_class, "Command"));

        $handler_class = $aggregate_namespace . "Handler";
        $aggregate_class = $aggregate_namespace . "Aggregate";
        $state_class = $aggregate_namespace . "State";

        $repository = new Repository(
            $this->app->make('EventLog'),
            $this->app->make('BoundedContext\Projection\AggregateCollections\Projection'),
            new $aggregate_class(Uuid::null(), new $state_class, new Collection())
        );

        $namespaced_handler_class = '\\' . $handler_class;
        $namespaced_handler_class = new \ReflectionClass($namespaced_handler_class);
        $parameters = $namespaced_handler_class->getMethods()[0]->getParameters();

        if(count($parameters) == 1)
        {
            return new $handler_class($repository);
        }

        array_shift($parameters);

        $params_array = [];
        foreach($parameters as $parameter)
        {
            $params_array[] = $this->app->make($parameter->getClass()->getName());
        }

        array_unshift($params_array, $repository);

        $r = new \ReflectionClass($handler_class);

        return $r->newInstanceArgs($params_array);
    }
}
