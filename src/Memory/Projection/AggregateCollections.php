<?php

namespace BoundedContext\Laravel\Memory\Projection;

use BoundedContext\Collection\Collection;
use BoundedContext\Log\Item;
use BoundedContext\Projection\AbstractProjection;
use BoundedContext\ValueObject\Uuid;
use BoundedContext\Contracts\Projection;
use BoundedContext\ValueObject\Version;

class AggregateCollections extends AbstractProjection implements Projection\AggregateCollections
{
    private $aggregates;

    public function __construct(Uuid $last_id, Version $version, Version $count)
    {
        parent::__construct($last_id, $version, $count);

        $this->aggregates = [];
    }

    public function reset()
    {
        parent::reset();

        $this->aggregates = [];
    }

    public function exists(Uuid $id)
    {
        return array_key_exists($id->serialize(), $this->aggregates);
    }

    public function get(Uuid $id)
    {
        if(!$this->exists($id))
        {
            throw new \Exception("Aggregate [".$id->serialize()."] does not exist.");
        }

        $items = $this->aggregates[$id->serialize()];

        $events = new Collection();
        foreach($items as $item)
        {
            $events->append($item->event());
        }

        return $events;
    }

    public function save()
    {

    }

    public function append(Item $item)
    {
        $id = $item->event()->id();

        if(!$this->exists($id))
        {
            $this->aggregates[$id->serialize()] = new Collection();
        }

        $this->aggregates[$id->serialize()]->append($item);
    }

    public function append_collection(Collection $items)
    {
        foreach($items as $item)
        {
            $this->append($item);
        }
    }
}