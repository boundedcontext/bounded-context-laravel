<?php namespace BoundedContext\Laravel\Illuminate\Projection;

use BoundedContext\Contracts\ValueObject\Identifier;
use Illuminate\Contracts\Foundation\Application;

class Factory implements \BoundedContext\Contracts\Projection\Factory
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function id(Identifier $id)
    {
        $class_name = preg_replace(
            '/Projector$/', 'Projection',
            $id->serialize()
        );

        return $this->app->make($class_name);
    }
}
