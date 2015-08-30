<?php

namespace BoundedContext\Laravel\Illuminate\Workflow;

use BoundedContext\ValueObject\Uuid;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;

abstract class AbstractWorkflow extends \BoundedContext\Workflow\AbstractWorkflow
{
    protected $dispatcher;
    protected $connection;
    protected $table;
    protected $name;

    public function __construct(Application $app)
    {
        $this->dispatcher = $app->make('Illuminate\Contracts\Bus\Dispatcher');
        $this->log = $app->make('BoundedContext\Contracts\Log');
        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get('bounded-context.database.tables.workflows');

        $this->name = get_called_class();

        $workflow_row = $this->get_workflow();

        parent::__construct(
            $this->log,
            new Uuid($workflow_row->last_id)
        );

        $this->connection->beginTransaction();
    }

    public function query()
    {
        return $this->connection->table($this->table);
    }

    private function get_workflow()
    {
        $workflow_row = $this->connection->table($this->table)
            ->sharedLock()
            ->where('name', $this->name)
            ->first();

        if(!$workflow_row)
        {
            throw new \Exception("The Workflow [".$this->name."] does not exist.");
        }

        return $workflow_row;
    }

    protected function save_workflow()
    {
        $this->query()
            ->where('name', $this->name)
            ->update([
                'last_id' => $this->last_id->serialize()
            ]);
    }

    public function reset()
    {
        parent::reset();
        $this->save_workflow();

        $this->connection->commit();
    }

    public function play()
    {
        parent::play();
        $this->save_workflow();

        $this->connection->commit();
    }
}