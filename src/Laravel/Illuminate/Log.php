<?php

namespace BoundedContext\Laravel\Illuminate;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Laravel\Item\Upgrader;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Contracts\Core\Collectable;
use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\Collection\Collection as CollectionContract;

class Log implements \BoundedContext\Contracts\Sourced\Log
{
    private $connection;
    private $upgrader;
    private $table;

    public function __construct(
        Upgrader $upgrader,
        DatabaseManager $manager,
        $table = 'event_log',
        $stream_table = 'event_stream'
    )
    {
        $this->upgrader = $upgrader;
        $this->connection = $manager->connection();

        $this->table = $table;
        $this->stream_table =$stream_table;
    }

    public function query()
    {
        return $this->connection->table($this->table);
    }

    public function reset()
    {
        $this->connection->table($this->table)
            ->delete();

        $this->connection->table($this->stream_table)
            ->delete();
    }

    private function get_starting_id(Identifier $id)
    {
        if($id->is_null())
        {
            return 0;
        }

        $query = $this->connection->table($this->stream_table)
            ->where('log_item_id', '=', $id->serialize())
            ->first();

        if(!$query)
        {
            throw new \Exception("The uuid [".$id->serialize()."] does not exist in log.");
        }

        return $query->log_id;
    }

    private function get_serialized_items(Identifier $id, $limit)
    {
        $starting_id = $this->get_starting_id($id);

        $item_records = $this->connection->table($this->table)
            ->where('id', '>', $starting_id)
            ->limit($limit)
            ->get();

        $items = [];

        foreach($item_records as $item_record)
        {
            $items[] = json_decode($item_record->item, true);
        }

        return $items;
    }

    public function get_collection(Identifier $id, $limit = 1000)
    {
        $serialized_items = $this->get_serialized_items($id, $limit);

        $items = new Collection();

        foreach($serialized_items as $serialized_item)
        {
            $items->append(
                $this->upgrader->deserialize($serialized_item)
            );
        }

        return $items;
    }

    public function append(Collectable $event)
    {
        $item = $this->upgrader->generate($event);

        $id = $this->connection->table($this->table)->insertGetId(array(
            'item' => json_encode($item->serialize())
        ));

        $this->connection->table($this->stream_table)->insert([
            'log_id' => $id,
            'log_item_id' => $item->id()->serialize(),
        ]);
    }

    public function append_collection(CollectionContract $events)
    {
        foreach($events as $event)
        {
            $this->append($event);
        }
    }
}