<?php namespace BoundedContext\Laravel\Illuminate\Projector;

use Illuminate\Contracts\Foundation\Application;

class Factory implements \BoundedContext\Contracts\Projector\Factory
{
    private $app;

    private $event_log;

    private $projection_factory;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->event_log = $this->app->make('EventLog');

        $this->projection_factory = $app->make('');
    }

    public function snapshot(Snapshot $snapshot, $namespace)
    {
        return new $namespace(
            $this->event_log,
            $this->projection_factory->id($namespace),
            $snapshot
        );
    }
}
