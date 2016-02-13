<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use BoundedContext\Contracts\ValueObject\Identifier;

use BoundedContext\Collection\Collection;
use BoundedContext\Schema\Schema;
use BoundedContext\Sourced\Stream\AbstractStream;
use BoundedContext\ValueObject\Integer as Integer_;

use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
use BoundedContext\Laravel\Event\Factory as EventFactory;
use Illuminate\Database\ConnectionInterface;

class Stream extends AbstractStream implements \BoundedContext\Contracts\Sourced\Stream\Stream
{
    protected $connection;
    protected $stream_table = 'event_snapshot_stream';
    protected $log_table = 'event_snapshot_log';

    protected $starting_id;
    protected $last_id;

    public function __construct(
        ConnectionInterface $connection,
        EventFactory $event_factory,
        EventSnapshotFactory $event_snapshot_factory,
        Identifier $starting_id,
        Integer_  $limit,
        Integer_ $chunk_size
    )
    {
        $this->connection = $connection;

        $this->starting_id = $starting_id;
        $this->last_id = $starting_id;

        parent::__construct(
            $event_factory,
            $event_snapshot_factory,
            $limit,
            $chunk_size
        );
    }

    public function reset()
    {
        $this->last_id = $this->starting_id;

        parent::reset();
    }

    private function get_next_chunk()
    {
        $rows = $this->connection
            ->table($this->stream_table)
            ->select("$this->log_table.snapshot")
            ->join(
                $this->log_table,
                "$this->stream_table.log_id",
                '=',
                "$this->log_table.id"
            )
            ->where(
                "$this->stream_table.log_snapshot_id", ">",
                $this->last_id->serialize()
            )
            ->orderBy("$this->stream_table.id")
            ->limit($this->chunk_size->serialize())
            ->get();

        return $rows;
    }

    protected function fetch()
    {
        $this->event_snapshot_schemas = new Collection();

        $event_snapshot_schemas = $this->get_next_chunk();

        foreach($event_snapshot_schemas as $event_snapshot_schema)
        {
            $event_snapshot = $this->event_snapshot_factory->schema(
                new Schema(
                    json_decode(
                        $event_snapshot_schema->snapshot,
                        true
                    )
                )
            );

            $this->event_snapshot_schemas->append($event_snapshot);
            $this->last_id = $event_snapshot->id();
        }
    }
}
