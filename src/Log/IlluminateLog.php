<?php

namespace BoundedContext\Laravel\Log;

use BoundedContext\Laravel\Illuminate\Item\Upgrader;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

use BoundedContext\Collection\Collectable;
use BoundedContext\Stream\Stream;
use BoundedContext\ValueObject\Uuid;
use BoundedContext\Collection\Collection;

class IlluminateLog implements \BoundedContext\Contracts\Log
{
    private $connection;
    private $upgrader;
    private $query;

    public function __construct(
        Upgrader $upgrader,
        Connection $connection,
        $table = 'event_log'
    )
    {
        $this->upgrader = $upgrader;
        $this->connection = $connection;
        $this->query = $this->connection->table($table);
    }

    public function get_stream(Uuid $id = null)
    {
        $stream = new Stream($this);

        if(!is_null($id))
        {
            $stream->move_to($id);
        }

        return $stream;
    }

    private function get_starting_id(Uuid $id = null)
    {
        if(is_null($id))
        {
            return 0;
        }

        $query = $this->query
            ->where('item_id', '=', $id->serialize())
            ->first();

        if(!$query)
        {
            throw new \Exception("The uuid [$id->serialize()] does not exist in log.");
        }

        return $query->id;
    }

    private function get_serialized_items(Uuid $id = null, $limit)
    {
        $starting_id = $this->get_starting_id($id);

        $item_records = $this->query
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

    public function get_collection(Uuid $id = null, $limit = 1000)
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

        $this->query->insert(array(
            'item_id' => $item->id()->serialize(),
            'item' => json_encode($item->serialize())
        ));

        return $item;
    }

    public function append_collection(Collection $events)
    {
        $items = [];
        $generated_items = new Collection();

        foreach($events as $event)
        {
            $item = $this->upgrader->generate($event);

            $generated_items->append($item);

            $items[] = [
                'item' => json_encode($item->serialize())
            ];
        }

        $this->query->insert(array(
            'item_id' => $item->id()->serialize(),
            'item' => json_encode($item->serialize())
        ));

        return $generated_items;
    }
}