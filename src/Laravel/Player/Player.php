<?php namespace BoundedContext\Laravel\Illuminate\Player;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Player\Repository;

class CollectionPlayer
{
    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function reset(Collection $namespaces)
    {
        foreach($namespaces as $ns)
        {
            $player = $this->repository->get($ns);

            $player->reset();

            $this->repository->save($player);
        }
    }

    public function play(Collection $namespaces, $limit = 1000)
    {
        foreach($namespaces as $ns)
        {
            $player = $this->repository->get($ns);

            $player->play($limit);

            $this->repository->save($player);
        }
    }
}
