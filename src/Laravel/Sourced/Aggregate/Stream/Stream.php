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
            ->where('aggregate_id', $this->aggregate_id->serialize())
            ->orderBy('id')
            ->limit($this->chunk_size->serialize())
            ->offset($this->current_offset->serialize())
            ->get();

        if(count($rows) < $this->chunk_size)
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
                new Schema($row)
            );

            $events->append(
                $this->event_factory->snapshot($event_snapshot)
            );
        }

        return $events;
    }

    public function current()
    {
        return $this->event_collection->current();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
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

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->event_collection->key();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->event_collection->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->event_collection->rewind();
    }
}
