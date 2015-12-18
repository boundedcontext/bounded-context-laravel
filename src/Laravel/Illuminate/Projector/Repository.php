<?php namespace BoundedContext\Laravel\Illuminate\Projector;

use Illuminate\Contracts\Foundation\Application;
use BoundedContext\Contracts\Projector\Projector;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\ValueObject\Integer;

class Repository implements \BoundedContext\Contracts\Projector\Repository
{
    private $app;
    private $connection;
    private $table;

    private $projector_factory;
    private $identifier_factory;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get(
            'bounded-context.database.tables.projectors'
        );

        $this->projector_factory = $app->make(
            'BoundedContext\Contracts\Projector\Factory'
        );

        $this->identifier_factory = $app->make(
            'BoundedContext\Contracts\Factory\Uuid'
        );
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function get(Identifier $namespace)
    {
        $row = $this->query()
            ->sharedLock()
            ->where('name', $namespace->serialize())
            ->first();

        if(!$row)
        {
            throw new \Exception("The Projector [".$namespace->serialize()."] does not exist.");
        }

        return $this->projector_factory->create(
            $namespace,
            new Snapshot(
                $this->identifier_factory->string($row->last_id),
                new Integer($row->version)
            )
        );
    }

    public function save(Projector $projector)
    {
        $class_name = get_class($projector);

        $this->query()
            ->where('name', $class_name)
            ->update($projector->snapshot()->serialize());
    }
}
