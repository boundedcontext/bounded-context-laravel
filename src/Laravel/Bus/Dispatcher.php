<?php

namespace BoundedContext\Laravel\Bus;

use BoundedContext\Laravel\Illuminate\Projector;
use BoundedContext\Collection\Collection;
use BoundedContext\Repository\Repository;
use BoundedContext\ValueObject\Uuid;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

class Dispatcher implements BusDispatcher
{
    private $app;
    private $projectors_repository;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->projectors_repository = new Projector\Repository($this->app);
    }

    private function generate_handler($aggregate_namespace, $aggregate_collections_projector)
    {
        $handler_class = $aggregate_namespace . "Handler";
        $aggregate_class = $aggregate_namespace . "Aggregate";
        $state_class = $aggregate_namespace . "State";

        $repository = new Repository(
            $this->app->make('BoundedContext\Contracts\Log'),
            $aggregate_collections_projector,
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

    private function play_projectors_by_bounded_context($bounded_context_namespace)
    {
        $reflect = new \ReflectionClass($this->app);

        $bindings = $reflect->getProperty('bindings');
        $bindings->setAccessible(true);
        $bindings = array_keys($bindings->getValue($this->app));

        $bounded_context_namespace_bindings = preg_grep(
            "/^".str_replace("\\", "\\\\", $bounded_context_namespace)."/",
            $bindings
        );

        $bounded_context_projection_namespaces = preg_grep(
            "/Projection$/",
            $bounded_context_namespace_bindings
        );

        foreach($bounded_context_projection_namespaces as $projection_namespace)
        {
            $projector_namespace = preg_replace('/Projection$/', 'Projector', $projection_namespace);

            $projector = $this->projectors_repository->get($projector_namespace);

            $projector->play();

            $this->projectors_repository->save($projector);
        }
    }

    public function dispatchFromArray($command, array $array)
    {

    }

    public function dispatchFrom($command, \ArrayAccess $source, array $extras = array())
    {

    }

    public function dispatch($command, \Closure $afterResolving = null)
    {
        $class = get_class($command);
        $bounded_context_namespace = substr($class, 0, strpos($class, "Aggregate"));
        $aggregate_namespace = substr($class, 0, strpos($class, "Command"));

        $aggregate_collections_projector = $this->projectors_repository->get('BoundedContext\Projection\AggregateCollections\Projector');

        $handler = $this->generate_handler($aggregate_namespace, $aggregate_collections_projector);
        $handler->handle($command);

        $this->projectors_repository->save($aggregate_collections_projector);

        $this->play_projectors_by_bounded_context(
            $bounded_context_namespace
        );
    }

    public function dispatchNow($command, \Closure $afterResolving = null)
    {

    }

    public function pipeThrough(array $pipes)
    {

    }
}