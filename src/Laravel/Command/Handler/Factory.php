<?php namespace BoundedContext\Laravel\Command\Handler;

use BoundedContext\Contracts\Command\Command;
use Illuminate\Foundation\Application;

class Factory
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function command(Command $command)
    {
        $command_class = get_class($command);

        $handler_class =
            '\\' .
            substr(
                $command_class, 0, strpos($command_class, "Command")
            ) .
            "Handler";

        return $this->application->make($handler_class);
    }
}
