<?php namespace BoundedContext\Laravel\Illuminate\Workflow;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Contracts\Workflow\Workflow;
use Illuminate\Contracts\Foundation\Application;

class Repository implements \BoundedContext\Contracts\Workflow\Repository
{
    private $app;
    private $connection;
    private $table;
    private $generator;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->connection = $app->make('db');

        $this->table = $app->make('config')->get(
            'bounded-context.database.tables.workflows'
        );

        $this->generator = $app->make('BoundedContext\Contracts\Generator\Uuid');
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

        $namespace_class = $namespace->serialize();

        return new $namespace_class(
            $this->app->make('EventLog'),
            $this->app->make('BoundedContext\Contracts\Bus\Dispatcher'),
            $this->generator->string($row->last_id)
        );
    }

    public function save(Workflow $workflow)
    {
        $class_name = get_class($workflow);

        $this->query()
            ->where('name', $class_name)
            ->update(array(
                'last_id' => $workflow->last_id()->serialize()
            ));
    }
}
