<?php namespace BoundedContext\Laravel\Bus;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Sourced\Log\Log as CommandLog;
use BoundedContext\Contracts\Sourced\Aggregate\Repository as AggregateRepository;

use Illuminate\Database\Connection;

class Dispatcher implements \BoundedContext\Contracts\Bus\Dispatcher
{
    private $connection;
    private $command_log;
    private $aggregate_repository;

    public function __construct(
        Connection $connection,
        AggregateRepository $aggregate_repository,
        CommandLog $command_log
    )
    {
        $this->connection = $connection;
        $this->aggregate_repository = $aggregate_repository;
        $this->command_log = $command_log;
    }

    protected function run(Command $command)
    {
        $aggregate = $this->aggregate_repository->by($command);

        $aggregate->handle($command);

        $this->aggregate_repository->save(
            $aggregate
        );

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
