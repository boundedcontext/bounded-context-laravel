<?php

namespace BoundedContext\Laravel\Illuminate\Projector;

use Illuminate\Contracts\Foundation\Application;

use BoundedContext\ValueObject\Uuid;
use BoundedContext\ValueObject\Version;

class Player
{
    public function __construct(Application $app, $bounded_context_namespace)
    {
        $this->bounded_context_namespace = $bounded_context_namespace;

        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get('bounded-context.database.tables.projectors');
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function reset()
    {
        $this->connection->beginTransaction();

        parent::reset();

        $this->save_projector();

        $this->connection->commit();
    }

    public function play()
    {
        $this->connection->beginTransaction();

        $this->get_projector();

        parent::play();

        $this->save_projector();

        $this->connection->commit();
    }

    private function generate_projector()
    {
        $projector_row = $this->query()
            ->sharedLock()
            ->where('name', $this->name)
            ->first();

        if(!$projector_row)
        {
            throw new \Exception("The Projector [$this->name] does not exist.");
        }

        $this->last_id = new Uuid($projector_row->last_id);
        $this->version = new Version($projector_row->version);
        $this->count = new Version($projector_row->processed);
    }

    private function save_projector()
    {
        $this->query()
            ->where('name', $this->name)
            ->update(array(
                'last_id' => $this->last_id->serialize(),
                'version' => $this->version->serialize(),
                'processed' => $this->count->serialize()
            ));
    }
}