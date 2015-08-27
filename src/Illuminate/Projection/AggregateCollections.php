<?php

namespace BoundedContext\Laravel\Illuminate\Projection;

use BoundedContext\Collection\Collection;
use BoundedContext\Log\Item;
use BoundedContext\Contracts\Projection;
use BoundedContext\ValueObject\Uuid;

class AggregateCollections extends AbstractProjection implements Projection\AggregateCollections
{
    protected function table()
    {
        return 'projections_core_aggregate_collections';
    }

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
            ->where('aggregate_id', $id->serialize())
            ->orderBy('id', 'ASC')
            ->get();

        if(!$serialized_items)
        {
            throw new \Exception("Aggregate [".$id->serialize()."] does not exist.");
        }

        $items = new Collection();

        foreach($serialized_items as $serialized_item)
        {
            $serialized_item = json_decode($serialized_item->item, true);

            $item = $this->upgrader->deserialize($serialized_item);
            $items->append($item);
        }

        return $items;
    }

    public function append(Item $item)
    {
        $this->query()->insert([
            'aggregate_id' => $item->event()->id()->serialize(),
            'item' => json_encode($item->serialize())
        ]);
    }

    public function append_collection(Collection $items)
    {
        $batch = [];
        foreach($items as $item)
        {
            $batch[] = [
                'aggregate_id' => $item->event()->id()->serialize(),
                'item' => json_encode($item->serialize())
            ];
        }

        $this->query()->insert($batch);
    }
}
