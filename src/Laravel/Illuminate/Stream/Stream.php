<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Snapshot\Factory;
use BoundedContext\Contracts\Event\Snapshot\Snapshot;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Schema\Schema;
use BoundedContext\ValueObject\Integer as Version;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\Collection\Collection as CollectionContract;

class Stream implements \BoundedContext\Contracts\Sourced\Aggregate\Stream\Stream
{
    private $event_snapshot_factory;
    private $connection;

    private $table;
    private $stream_table;

    public function __construct(
        Factory $event_snapshot_factory,
        DatabaseManager $manager,
        $table,
        $stream_table
    )
    {
        $this->event_snapshot_factory = $event_snapshot_factory;
        $this->connection = $manager->connection();

        $this->table = $table;
        $this->stream_table =$stream_table;
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

    /**
     * Sets that the Stream should look for snapshots after a version.
     *
     * @param Version $version
     * @return \BoundedContext\Contracts\Sourced\Aggregate\Stream\Stream
     */
    public function after(Version $version)
    {
        // TODO: Implement after() method.
    }

    /**
     * Sets that the Stream should look for snapshots with an id.
     *
     * @return \BoundedContext\Contracts\Sourced\Aggregate\Stream\Stream
     */
    public function with(Identifier $id)
    {
        // TODO: Implement with() method.
    }

    /**
     * Gets the resulting Snapshots.
     *
     * @return CollectionContract
     */
    public function as_collection()
    {
        // TODO: Implement as_collection() method.
    }
}