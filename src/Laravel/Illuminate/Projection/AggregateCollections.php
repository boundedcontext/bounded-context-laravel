<?php

namespace BoundedContext\Laravel\Illuminate\Projection;

use BoundedContext\Collection\Collection;
use BoundedContext\Log\Item;
use BoundedContext\Projection\AggregateCollections\Projection;
use BoundedContext\ValueObject\Uuid;

class AggregateCollections extends AbstractProjection implements Projection
{
    protected $table = 'projections_core_aggregate_collections';
    protected $event_log_table = 'event_log';
    protected $event_stream_table = 'event_stream';

    public function exists(Uuid $id)
    {
        $item_count = $this->query()
            ->where('aggregate_id', $id->serialize())
            ->count();

        return $item_count > 0;
    }

    public function get(Uuid $id)
    {
        $serialized_items = $this->query()
            ->join(
                $this->event_log_table,
                $this->table.'.event_log_id',
                '=' ,
                $this->event_log_table.'.id'
            )
            ->where('aggregate_id', $id->serialize())
            ->orderBy($this->event_log_table.'.id', 'ASC')
            ->get();

        if(!$serialized_items)
        {
            throw new \Exception("Aggregate [".$id->serialize()."] does not exist.");
        }

        $items = new Collection();

        $upgrader = $this->application->make('BoundedContext\Laravel\Item\Upgrader');

        foreach($serialized_items as $serialized_item)
        {
            $serialized_item = json_decode($serialized_item->item, true);

            $item = $upgrader->deserialize($serialized_item);
            $items->append($item);
        }

        return $items;
    }

    public function append(Item $item)
    {
        $event_stream_row = $this->connection->table($this->event_stream_table)
            ->where('event_log_item_id', $item->id()->serialize())
            ->first();

        if(!$event_stream_row)
        {
            throw new \Exception("The event stream does not contain an item for [".$item->id()->serialize()."].");
        }

        $this->query()->insert([
            'event_log_id' => $event_stream_row->event_log_id,
            'aggregate_id' => $item->event()->id()->serialize()
        ]);
    }

    public function append_collection(Collection $items)
    {
        foreach($items as $item)
        {
            $this->append($item);
        }
    }
}
