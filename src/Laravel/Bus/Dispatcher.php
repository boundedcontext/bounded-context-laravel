<?php

namespace BoundedContext\Laravel\Bus;

use BoundedContext\Laravel\Command\Handler;
use BoundedContext\Laravel\Illuminate\Projector;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

class Dispatcher implements BusDispatcher
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function dispatchFromArray($command, array $array)
    {

    }

    public function dispatchFrom($command, \ArrayAccess $source, array $extras = array())
    {

    }

    public function dispatch($command, \Closure $afterResolving = null)
    {
        $handler = (new Handler\Factory($this->app, $command))->generate();
        $handler->handle($command);

        $player = new Projector\Player($this->app, 'core');
        $player->play();

        $player = new Projector\Player($this->app, 'domain');
        $player->play();
    }

    public function dispatchNow($command, \Closure $afterResolving = null)
    {

    }

    public function pipeThrough(array $pipes)
    {

    }
}