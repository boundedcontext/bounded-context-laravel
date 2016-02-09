<?php namespace BoundedContext\Laravel\Sourced\Aggregate;

use BoundedContext\Contracts\Business\Invariant\Factory as InvariantFactory;
use BoundedContext\Contracts\Sourced\Aggregate\State\State;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\Factory
{
    protected $invariant_factory;

    public function __construct(InvariantFactory $invariant_factory)
    {
        $this->invariant_factory = $invariant_factory;
    }

    public function state(State $state)
    {
        $state_class = get_class($state);

        $state_prefix = substr(
            $state_class,
            0,
            strpos($state_class, "State")
        );

        $aggregate_class = $state_prefix . "Aggregate";

        return new $aggregate_class(
            $this->invariant_factory,
            $state
        );
    }
}
