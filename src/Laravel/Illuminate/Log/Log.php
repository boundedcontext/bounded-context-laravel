<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Event;
use BoundedContext\Contracts\Event\Snapshot\Factory;
use BoundedContext\Contracts\Sourced\Aggregate\Stream\Builder;
use Illuminate\Database\DatabaseManager;
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

    public function append(Event $event)
    {
        $snapshot = $this->event_snapshot_factory->event($event);

        $id = $this->connection->table($this->table)->insertGetId(array(
            'snapshot' => json_encode($snapshot->serialize())
        ));

        $this->connection->table($this->stream_table)->insert([
            'log_id' => $id,
            'log_snapshot_id' => $snapshot->id()->serialize(),
            'aggregate_id' => $snapshot->schema()->id,
            'version' => $snapshot->version()->serialize(),
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
