<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Snapshot\Factory;
use BoundedContext\Contracts\Event\Snapshot\Snapshot;
use BoundedContext\Contracts\Sourced\Aggregate\Stream\Builder;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Schema\Schema;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\Collection\Collection as CollectionContract;

class Log implements \BoundedContext\Contracts\Sourced\Log\Log
{
    private $event_snapshot_factory;
    private $aggregate_stream_builder;
    private $connection;

    private $table;
    private $stream_table;

    public function __construct(
        Factory $event_snapshot_factory,
        Builder $aggregate_stream_builder,
        DatabaseManager $manager,
        $table,
        $stream_table
    )
    {
        $this->event_snapshot_factory = $event_snapshot_factory;
        $this->aggregate_stream_builder = $aggregate_stream_builder;
        $this->connection = $manager->connection();

        $this->table = $table;
        $this->stream_table =$stream_table;
    }

    public function builder()
    {
        return $this->aggregate_stream_builder;
    }

    public function query()
    {
        return $this->connection
            ->table($this->table);
    }

    public function reset()
    {
        $this->connection
            ->table($this->table)
            ->delete();

        $this->connection
            ->table($this->stream_table)
            ->delete();
    }

    public function get_collection(Identifier $id, $limit = 1000)
    {
        $snapshot_records = $this->connection->table($this->table)
            ->where('id', '>', $id->serialize())
            ->limit($limit)
            ->get();

        $snapshots = new Collection();

        foreach($snapshot_records as $snapshot_record)
        {
            $snapshots->append(
                $this->event_snapshot_factory->schema(
                    new Schema(json_decode($snapshot_record, true))
                )
            );
        }

        return $snapshots;
    }

    public function append(Snapshot $snapshot)
    {
        $id = $this->connection->table($this->table)->insertGetId(array(
            'item' => json_encode($snapshot->serialize())
        ));

        $this->connection->table($this->stream_table)->insert([
            'log_id' => $id,
            'log_item_id' => $snapshot->id()->serialize(),
        ]);
    }

    public function append_collection(CollectionContract $snapshots)
    {
        foreach($snapshots as $snapshot)
        {
            $this->append($snapshot);
        }
    }
}