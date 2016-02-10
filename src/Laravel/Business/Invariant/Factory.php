<?php namespace BoundedContext\Laravel\Business\Invariant;

use BoundedContext\Contracts\Projection\Queryable;
use Illuminate\Contracts\Foundation\Application;

class Factory implements \BoundedContext\Contracts\Business\Invariant\Factory
{
    protected $app;
    protected $queryable;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->queryable = null;
    }

    public function with(Queryable $queryable)
    {
        $this->queryable = $queryable;

        return $this;
    }

    public function by_class($class, $params = [])
    {
        $reflection_class = new \ReflectionClass($class);
        $state_queryable_class = get_class($this->queryable);

        $queryable_name = $reflection_class
            ->getMethod('satisfier')
            ->getParameters()[0]
            ->getClass()
            ->name
        ;

        if($queryable_name == $state_queryable_class)
        {
            return new $class($this->queryable);
        }

        return new $class(
            $this->app->make($queryable_name)
        );
    }
}
