<?php namespace BoundedContext\Laravel\Sourced\Aggregate\Stream;

use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
use BoundedContext\Laravel\Event\Factory as EventFactory;
use BoundedContext\Laravel\Illuminate\Projection\AbstractQueryable;
use BoundedContext\Schema\Schema;
use BoundedContext\ValueObject\Integer as Integer_;
use Illuminate\Contracts\Foundation\Application;

class Stream extends AbstractQueryable implements \BoundedContext\Contracts\Sourced\Aggregate\Stream\Stream
{
    protected $event_factory;
    protected $event_snapshot_factory;

    protected $event_collection;

    protected $aggregate_id;

    protected $starting_offset;
    protected $current_offset;

    protected $chunk_size;
    protected $has_more_chunks;

    protected $table = 'event_snapshot_stream';

    public function __construct(
        Application $app,
        EventFactory $event_factory,
        EventSnapshotFactory $event_snapshot_factory,
        Identifier $aggregate_id,
        Integer_ $starting_offset,
        Integer_ $chunk_size
    )
    {
        parent::__construct($app);

        $this->event_factory = $event_factory;
        $this->event_snapshot_factory = $event_snapshot_factory;

        $this->aggregate_id = $aggregate_id;

        $this->starting_offset = $starting_offset;
        $this->current_offset = $starting_offset;

        $this->chunk_size = $chunk_size;
        $this->has_more_chunks = true;

        $this->event_collection = $this->fetch();
    }

    private function get_next_chunk()
    {
        $rows = $this->query()
            ->select('event_snapshot_log.snapshot')
            ->where('event_snapshot_stream.aggregate_id', $this->aggregate_id->serialize())
            ->orderBy('event_snapshot_stream.id')
            ->join('event_snapshot_log', 'event_snapshot_stream.log_id', '=', 'event_snapshot_log.id')
            ->limit($this->chunk_size->serialize())
            ->offset($this->current_offset->serialize())
            ->get();

        if(count($rows) < $this->chunk_size->serialize())
        {
            $this->has_more_chunks = false;
        }

        return $rows;
    }

    private function fetch()
    {
        $rows = $this->get_next_chunk();

        $events = new Collection();
        foreach($rows as $row)
        {
            $event_snapshot = $this->event_snapshot_factory->schema(
                new Schema(json_decode($row->snapshot, true))
            );

            $events->append(
                $this->event_factory->snapshot($event_snapshot)
            );
        }

        $this->current_offset = $this->current_offset->add(
            new Integer_($events->count())
        );

        return $events;
    }

    public function current()
    {
        return $this->event_collection->current();
    }

    public function next()
    {
        $this->event_collection->next();

        if(!$this->event_collection->valid())
        {
            $this->event_collection->append_collection(
                $this->fetch()
            );
        }
    }

    public function key()
    {
        return $this->event_collection->key();
    }

    public function valid()
    {
        return $this->event_collection->valid();
    }

    public function rewind()
    {
        $this->event_collection->rewind();
    }
}
