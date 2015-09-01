<?php

namespace BoundedContext\Laravel\Illuminate\Projector;

use BoundedContext\Contracts\Projector;
use Illuminate\Contracts\Foundation\Application;

use BoundedContext\ValueObject\Uuid;
use BoundedContext\ValueObject\Version;

class Repository
{
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get('bounded-context.database.tables.projectors');
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function get($projector_namespace)
    {
        $projector_row = $this->query()
            ->sharedLock()
            ->where('name', $projector_namespace)
            ->first();

        if(!$projector_row)
        {
            throw new \Exception("The Projector [$projector_namespace] does not exist.");
        }

        $projection_namespace = preg_replace('/Projector$/', 'Projection', $projector_namespace);

        $projector = new $projector_namespace(
            $this->app->make('BoundedContext\Contracts\Log'),
            $this->app->make($projection_namespace),
            new Uuid($projector_row->last_id),
            new Version($projector_row->version),
            new Version($projector_row->processed)
        );

        return $projector;
    }

    public function save(Projector $projector)
    {
        $projector_name = get_class($projector);

        $this->query()
            ->where('name', $projector_name)
            ->update(array(
                'last_id' => $projector->last_id()->serialize(),
                'version' => $projector->version()->serialize(),
                'processed' => $projector->count()->serialize()
            ));
    }
}