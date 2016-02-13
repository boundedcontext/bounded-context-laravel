<?php namespace BoundedContext\Laravel\Player\Collection;

use BoundedContext\Collection\Collection;
use BoundedContext\Laravel\ValueObject\Uuid;
use BoundedContext\Player\Collection\Player;
use Illuminate\Support\Facades\Config;
use BoundedContext\Player\Repository;

class Builder
{
    protected $repository;
    protected $players;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->players = new Collection();
    }

    public function all()
    {
        $this->players = new Collection();

        $player_spaces = Config::get('players');

        foreach($player_spaces as $player_space => $player_types)
        {
            foreach($player_types as $player_type)
            {
                foreach($player_type as $player_id => $player_namespace)
                {
                    $this->players->append(
                        new Uuid($player_id)
                    );
                }
            }
        }

        return $this;
    }

    public function get()
    {
        return new Player(
            $this->repository,
            $this->players
        );
    }
}
