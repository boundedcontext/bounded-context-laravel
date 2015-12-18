<?php

namespace BoundedContext\Laravel\Bus;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Command\Log;
use BoundedContext\Laravel\Command\Handler\Factory;
use BoundedContext\Laravel\Illuminate\Player\CollectionPlayer;
use BoundedContext\Laravel\Illuminate\Projector;
use Illuminate\Database\Connection;

class Dispatcher implements \BoundedContext\Contracts\Bus\Dispatcher
{
    private $connection;
    private $log;
    private $factory;
    private $collection_player;

    public function __construct(
        Connection $connection,
        Log $log,
        Factory $factory,
        CollectionPlayer $collection_player)
    {
        $this->connection = $connection;
        $this->log = $log;
        $this->factory = $factory;
        $this->collection_player = $collection_player;
    }

    private function run(Command $command)
    {
        $handler = $this->factory->command($command);

        $this->collection_player->play(
            $handler->handle($command)
        );

        $this->log->append($command);
    }

    public function dispatch(Command $command)
    {
        $this->connection->beginTransaction();

        $this->run($command);

        $this->connection->commit();
    }

    public function dispatch_collection(Collection $commands)
    {
        $this->connection->beginTransaction();

        foreach($commands as $command)
        {
            $this->run($command);
        }

        $this->connection->commit();
    }
}
