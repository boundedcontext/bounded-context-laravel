<?php namespace BoundedContext\Laravel\Bus;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Command\Command;

use BoundedContext\Contracts\Event\Snapshot\Factory as CommandSnapshotFactory;
use BoundedContext\Contracts\Sourced\Log\Log as CommandLog;

use BoundedContext\Contracts\Sourced\Aggregate\Player\Factory as AggregatePlayerFactory;
use BoundedContext\Contracts\Sourced\Aggregate\Repository as AggregateRepository;

use Illuminate\Database\Connection;

class Dispatcher implements \BoundedContext\Contracts\Bus\Dispatcher
{
    private $connection;
    private $command_log;
    private $aggregate_repository;
    private $aggregate_player_factory;

    public function __construct(
        Connection $connection,
        AggregateRepository $aggregate_repository,
        AggregatePlayerFactory $aggregate_player_factory,
        CommandLog $command_log
    )
    {
        $this->connection = $connection;
        $this->aggregate_repository = $aggregate_repository;
        $this->aggregate_player_factory = $aggregate_player_factory;
        $this->command_log = $command_log;
    }

    private function run(Command $command)
    {
        $aggregate = $this->aggregate_repository->by($command);

        dd($aggregate);

        $aggregate->handle($command);

        $this->aggregate_repository->save(
            $aggregate
        );

        $player = $this->aggregate_player_factory->aggregate(
            $aggregate
        );

        $player->play();

        $this->command_log->append($command);
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
