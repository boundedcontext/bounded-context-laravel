<?php

namespace BoundedContext\Laravel\Bus;

use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Laravel\Command\Handler;
use BoundedContext\Laravel\Illuminate\Projector;
use Illuminate\Contracts\Foundation\Application;

class Dispatcher implements \BoundedContext\Contracts\Bus\Dispatcher
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function dispatch(Command $command)
    {
        $handler = (new Handler\Factory($this->app, $command))->generate();
        $handler->handle($command);

        $player = new Projector\Player($this->app, 'core');
        $player->play();

        $player = new Projector\Player($this->app, 'domain');
        $player->play();

        $command_log = $this->app->make('CommandLog');
        $command_log->append($command);
    }

    public function dispatch_collection(Collection $commands)
    {
        foreach($commands as $command)
        {
            $this->dispatch($command);
        }
    }


}