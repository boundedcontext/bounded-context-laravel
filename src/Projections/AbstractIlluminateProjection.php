<?php

namespace BoundedContext\Laravel\Projections;

use BoundedContext\Projection\AbstractProjection;
use BoundedContext\ValueObject\Uuid;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

abstract class AbstractIlluminateProjection extends AbstractProjection implements \BoundedContext\Contracts\Projection
{
    private $connection;
    private $metadata_query;
    protected $query;
    private $called_class;

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

    public function __construct(
        Connection $connection,
        $projections_metadata_table,
        $projection_table
    )
    {
        $this->connection = $connection;
        $this->metadata_query = $this->connection->table(
            $projections_metadata_table
        );

        $this->query = $this->connection->table(
            $projection_table
        );

        $this->called_class = get_called_class();

        $metadata = $this->get_metadata();

        parent::__construct(
            new Uuid($metadata->id),
            $metadata->version,
            $metadata->count
        );

        $this->connection->beginTransaction();
        $this->connection->select("LOCK TABLES $projection_table WRITE");
    }

    public function reset()
    {
        parent::reset();

        $this->query->delete();

        $this->metadata_query
            ->where('name', $this->called_class)
            ->update(array(
                'last_id' => null,
                'version' => 0,
                'count' => 0
            ));
    }

    public function save()
    {
        $this->metadata_query
            ->where('name', $this->called_class)
            ->update(array(
                'last_id' => $this->last_id,
                'version' => $this->version,
                'count' => $this->count
            ));

        $this->connection->commit();
        $this->connection->select("UNLOCK TABLES");
    }
}