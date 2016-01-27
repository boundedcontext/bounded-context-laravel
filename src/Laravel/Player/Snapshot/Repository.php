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
        Factory $snapshot_factory,
        $table = 'player_snapshots'
    )
    {
        $this->app = $app;
        $this->connection = $app->make('db');

        $this->snapshot_factory = $snapshot_factory;

        $this->table = $table;
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function get(Identifier $id)
    {
        $row = $this->query()
            ->sharedLock()
            ->where('id', $id->serialize())
            ->first();

        if(!$row)
        {
            throw new \Exception("The Player Snapshot [".$id->serialize()."] does not exist.");
        }

        $row_array = (array) $row;
        return $this->snapshot_factory->make($row_array);
    }

    public function save(Snapshot $snapshot)
    {
        $this->query()
            ->where('id', $snapshot->id()->serialize())
            ->update(
                $snapshot->serialize()
            );
    }
}
