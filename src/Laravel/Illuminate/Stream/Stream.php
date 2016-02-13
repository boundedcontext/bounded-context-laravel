<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use BoundedContext\Contracts\ValueObject\Identifier;

use BoundedContext\Collection\Collection;
use BoundedContext\Schema\Schema;
use BoundedContext\Sourced\Stream\AbstractStream;
use BoundedContext\ValueObject\Integer as Integer_;

use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
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
        $query = $this->connection
            ->table($this->stream_table)
            ->select("$this->log_table.snapshot")
            ->join(
                $this->log_table,
                "$this->stream_table.log_id",
                '=',
                "$this->log_table.id"
            )
            ->orderBy("$this->stream_table.id")
            ->limit($this->chunk_size->serialize());

        if(!$this->last_id->is_null())
        {
            $query->whereRaw("
                $this->stream_table.id >
                    (
                        SELECT id FROM `$this->stream_table`
                        WHERE `$this->stream_table`.`log_snapshot_id` = '".$this->last_id->serialize()."'
                    )
                "
            );
        }

        $rows = $query->get();

        return $rows;
    }

    protected function fetch()
    {
        $this->event_snapshots = new Collection();

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

            $this->event_snapshots->append($event_snapshot);
            $this->last_id = $event_snapshot->id();
        }
    }
}
