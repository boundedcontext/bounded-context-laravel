<?php namespace BoundedContext\Laravel\Player\Snapshot;

use BoundedContext\Contracts\Player\Snapshot\Snapshot;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Player\Snapshot\Factory;

use Illuminate\Contracts\Foundation\Application;

class Repository implements \BoundedContext\Contracts\Player\Snapshot\Repository
{
    private $app;
    private $connection;
    private $table;

    protected $snapshot_factory;

    public function __construct(
        Application $app,
        Factory $snapshot_factory
    )
    {
        $this->app = $app;
        $this->connection = $app->make('db');

        $this->table = $app->make('config')->get(
            'bounded-context.database.tables.players'
        );

        $this->snapshot_factory = $snapshot_factory;
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function get(Identifier $namespace)
    {
        $row = $this->query()
            ->sharedLock()
            ->where('name', $namespace->serialize())
            ->first();

        if(!$row)
        {
            throw new \Exception("The Projector [".$namespace->serialize()."] does not exist.");
        }

        return $this->snapshot_factory->make($row);
    }

    public function save(Snapshot $snapshot)
    {
        $class_name = get_class($player);

        $this->query()
            ->where('name', $class_name)
            ->update(
                $player->snapshot()->serialize()
            );
    }
}
