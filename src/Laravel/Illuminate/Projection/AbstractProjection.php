<?php namespace BoundedContext\Laravel\Illuminate\Projection;

use BoundedContext\Contracts\Projection\Queryable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Query\Builder;

abstract class AbstractProjection
{
    protected $application;
    protected $connection;
    protected $queryable;
    protected $table = 'projection_table';

    public function __construct(Application $app, Queryable $queryable)
    {
        $this->application = $app;
        $this->connection = $app->make('db');

        $this->queryable = $queryable;
    }

    public function reset()
    {
        $this->query()->delete();
    }

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function queryable()
    {
        return $this->queryable;
    }
}
