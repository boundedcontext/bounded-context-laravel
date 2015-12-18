<?php namespace BoundedContext\Laravel\Illuminate\Projector;

use Illuminate\Contracts\Foundation\Application;

class Player
{
    public function __construct(Application $app, $type = null)
    {
        $this->app = $app;

        $this->connection = $app->make('db');
        $this->table = $app->make('config')->get(
            'bounded-context.database.tables.projectors'
        );

        $this->projector_repository = new Repository($app);
        $this->projectors = [];

        $this->generate_projectors($type);
    }

    private function convert_to_projector($projection_namespace)
    {
        return preg_replace('/Projection$/', 'Projector', $projection_namespace);
    }

    protected function generate_projectors($type)
    {
        if(is_null($type))
        {
            $projection_types = $this->app->make('config')->get('bounded-context.projections');
            foreach($projection_types as $projection_type)
            {
                foreach($projection_type as $abstract => $implemented)
                {
                    $this->projectors[] = $this->convert_to_projector($abstract);
                }
            }

            return true;
        }

        $projection_type = $this->app->make('config')->get('bounded-context.projections.'.$type);

        foreach($projection_type as $abstract => $implemented)
        {
            $this->projectors[] = $this->convert_to_projector($abstract);
        }
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function reset()
    {
        $this->connection->beginTransaction();

        foreach($this->projectors as $projector)
        {
            $p = $this->projector_repository->get($projector);

            $p->reset(
                $this->app->make('BoundedContext\Contracts\Generator\Uuid')
            );

            $this->projector_repository->save($p);
        }

        $this->connection->commit();
    }

    public function play()
    {
        $this->connection->beginTransaction();

        foreach($this->projectors as $projector)
        {
            $p = $this->projector_repository->get($projector);

            $p->play();

            $this->projector_repository->save($p);
        }

        $this->connection->commit();
    }
}