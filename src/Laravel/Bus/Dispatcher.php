<?php namespace BoundedContext\Laravel\Bus;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Command\Log;
use BoundedContext\Laravel\Command\Handler\Factory as HandlerFactory;
use BoundedContext\Contracts\Event\Snapshot\Factory as EventSnapshotFactory;

use Illuminate\Database\Connection;

class Dispatcher implements \BoundedContext\Contracts\Bus\Dispatcher
{
    private $connection;
    private $log;
    private $handler_factory;
    private $event_snapshot_factory;

    public function __construct(
        Connection $connection,
        Log $log,
        HandlerFactory $handler_factory,
        EventSnapshotFactory $event_snapshot_factory
    )
    {
        $this->connection = $connection;
        $this->log = $log;
        $this->handler_factory = $handler_factory;
        $this->event_snapshot_factory = $event_snapshot_factory;
    }

    private function run(Command $command)
    {
        $handler = $this->handler_factory->command($command);
        $handler->handle($command);

        $collection_player = $this->player_collection_repository->handler(
            $handler
        );

        $this->player_collection_repository->save(
            $collection_player->play()
        );

        $this->log->append(
            $this->event_snapshot_factory->event($command)
        );
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
