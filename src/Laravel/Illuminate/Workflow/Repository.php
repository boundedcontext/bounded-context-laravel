<?php

namespace BoundedContext\Laravel\Illuminate\Workflow;

use BoundedContext\ValueObject\Uuid;
use BoundedContext\Contracts\Workflow;
use Illuminate\Contracts\Foundation\Application;

class Repository
{
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get('bounded-context.database.tables.workflows');
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function get($workflow_namespace)
    {
        $workflow_row = $this->query()
            ->sharedLock()
            ->where('name', $workflow_namespace)
            ->first();

        if(!$workflow_row)
        {
            throw new \Exception("The Projector [$workflow_namespace] does not exist.");
        }

        return new $workflow_namespace(
            $this->app->make('EventLog'),
            $this->app->make('BoundedContext\Contracts\Bus\Dispatcher'),
            new Uuid($workflow_row->last_id)
        );
    }

    public function save(Workflow $workflow)
    {
        $workflow_name = get_class($workflow);

        $this->query()
            ->where('name', $workflow_name)
            ->update(array(
                'last_id' => $workflow->last_id()->serialize()
            ));
    }
}