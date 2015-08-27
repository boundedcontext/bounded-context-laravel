<?php

namespace BoundedContext\Laravel\Illuminate\Projection;

use BoundedContext\Laravel\Item\Upgrader;
use BoundedContext\Projection;
use BoundedContext\ValueObject\Uuid;
use BoundedContext\ValueObject\Version;
use Illuminate\Database\DatabaseManager;

abstract class AbstractProjection extends Projection\AbstractProjection implements \BoundedContext\Contracts\Projection
{
    protected $upgrader;
    protected $connection;

    private $metadata_table;
    private $table;

    private $metadata_query;
    protected $query;

    private $called_class;

    public function __construct(
        Upgrader $upgrader,
        DatabaseManager $manager,
        $projections_metadata_table = 'projections'
    )
    {
        $this->upgrader = $upgrader;
        $this->connection = $manager->connection();

        $this->metadata_table = $projections_metadata_table;
        $this->table = $this->table();

        $this->metadata_query = $this->connection->table(
            $projections_metadata_table
        );

        $this->called_class = get_called_class();

        $metadata = $this->get_metadata();

        parent::__construct(
            new Uuid($metadata->last_id),
            new Version($metadata->version),
            new Version($metadata->count)
        );

        $this->connection->beginTransaction();
        //$this->connection->select("LOCK TABLES ".$this->table()." WRITE");
    }

    protected function query()
    {
        return $this->connection->table($this->table());
    }

    abstract protected function table();

    private function get_metadata()
    {
        $metadata = $this->metadata_query
            ->sharedLock()
            ->where('name', $this->called_class)
            ->first();

        if(!$metadata)
        {
            throw new \Exception("The Projection [$this->called_class] does not exist.");
        }

        return $metadata;
    }

    public function reset()
    {
        parent::reset();

        $this->query()->delete();
        $this->save();
    }

    public function save()
    {
        $this->metadata_query
            ->where('name', $this->called_class)
            ->update(array(
                'last_id' => $this->last_id->serialize(),
                'version' => $this->version->serialize(),
                'count' => $this->count->serialize()
            ));

        $this->connection->commit();
        //$this->connection->select("UNLOCK TABLES");
    }
}