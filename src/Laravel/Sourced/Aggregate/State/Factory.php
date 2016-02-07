<?php namespace BoundedContext\Laravel\Sourced\Aggregate\State;

use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Snapshot;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\State\Factory
{
    protected $state_class;

    public function with(Command $command)
    {
        $command_class = get_class($command);

        dd($command_class);

        $this->state_class = $command;
    }

    public function snapshot(Snapshot $snapshot)
    {
        // TODO: Implement snapshot() method.
    }
}
