<?php namespace BoundedContext\Laravel\Sourced\Aggregate;

use BoundedContext\Contracts\Player\Repository as PlayerRepository;
use BoundedContext\Contracts\Sourced\Aggregate\State\State;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\Factory
{
    protected $player_repository;

    public function __construct(PlayerRepository $player_repository)
    {
        $this->player_repository = $player_repository;
    }

    public function state(State $state)
    {

    }
}
