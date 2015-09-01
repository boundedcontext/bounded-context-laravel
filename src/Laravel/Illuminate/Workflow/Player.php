<?php

namespace BoundedContext\Laravel\Illuminate\Workflow;

use Illuminate\Contracts\Foundation\Application;

class Player
{
    public function __construct(Application $app, $type = null)
    {
        $this->app = $app;

        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get('bounded-context.database.tables.workflows');
        $this->workflow_repository = new Repository($app);

        $this->workflows = [];

        $this->generate_workflow($type);
    }

    protected function generate_workflow($type)
    {
        if(is_null($type))
        {
            $workflow_types = $this->app->make('config')->get('bounded-context.workflows');
            foreach($workflow_types as $workflow_type)
            {
                foreach($workflow_type as $workflow)
                {
                    $this->workflows[] = $workflow;
                }
            }

            return true;
        }

        $workflow_type = $this->app->make('config')->get('bounded-context.workflows.'.$type);

        foreach($workflow_type as $workflow)
        {
            $this->workflows[] = $workflow;
        }
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function reset()
    {
        $this->connection->beginTransaction();

        foreach($this->workflows as $workflow)
        {
            $w = $this->workflow_repository->get($workflow);
            $w->reset();
            $this->workflow_repository->save($w);
        }

        $this->connection->commit();
    }

    public function play()
    {
        $this->connection->beginTransaction();

        foreach($this->workflows as $workflow)
        {
            $w = $this->workflow_repository->get($workflow);
            $w->play();
            $this->workflow_repository->save($w);
        }

        $this->connection->commit();
    }
}
