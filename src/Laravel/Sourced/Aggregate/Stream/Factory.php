<?php namespace BoundedContext\Laravel\Sourced\Aggregate\Stream;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\ValueObject\Integer as Integer_;
use Illuminate\Contracts\Foundation\Application;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\Stream\Factory
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function create(
        Identifier $aggregate_id,
        Integer_ $starting_offset,
        Integer_ $chunk_size
    )
    {
        return $this->application->make(
            'BoundedContext\Laravel\Sourced\Aggregate\Stream\Stream',
            [
                $this->application,
                $this->application->make('BoundedContext\Laravel\Event\Factory'),
                $this->application->make('BoundedContext\Laravel\Event\Snapshot\Factory'),
                $aggregate_id,
                $starting_offset,
                $chunk_size
            ]
        );
    }
}
