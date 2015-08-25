<?php

namespace BoundedContext\Laravel\Projections\AggregateCollections;

use BoundedContext\Collection\Collection;
use BoundedContext\Laravel\Illuminate\Item\Upgrader;
use BoundedContext\Laravel\Projections\AbstractIlluminateProjection;
use BoundedContext\Log\Item;
use BoundedContext\Contracts\Projection\AggregateCollections;
use BoundedContext\ValueObject\Uuid;
use Illuminate\Database\Connection;

class IlluminateProjection extends AbstractIlluminateProjection implements AggregateCollections\Projection
{
    private $upgrader;

    public function __construct(
        Upgrader $upgrader,
        Connection $connection,
        $metadata_table = 'projections',
        $table
    )
    {
        $this->upgrader = $upgrader;
        parent::__construct($connection, $metadata_table, $table);
    }

    public function exists(Uuid $id)
    {
        $item_count = $this->query
            ->where('aggregate_id', $id->serialize())
            ->count();

        return $item_count > 0;
    }

    public function get(Uuid $id)
    {
        $serialized_items = $this->query
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

            $item = $this->upgrader->get($serialized_item);
            $items->append($item);
        }

        return $items;
    }

    public function append(Item $item)
    {
        $this->query->insert([
            'aggregate_id' => $item->event()->id()->serialize(),
            'item' => $item->serialize()
        ]);
    }

    public function append_collection(Collection $items)
    {
        $batch = [];
        foreach($items as $item)
        {
            $batch[] = [
                'aggregate_id' => $item->event()->id()->serialize(),
                'item' => $item->serialize()
            ];
        }

        $this->query->insert($batch);
    }
}
