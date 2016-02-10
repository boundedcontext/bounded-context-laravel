<?php namespace BoundedContext\Laravel\Serializer;

use Illuminate\Contracts\Foundation\Application;

class Deserializer implements \BoundedContext\Contracts\Serializer\Deserializer
{
    protected $app;
    protected $identifier_generator;
    protected $datetime_generator;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function deserialize($class, $serialized = null)
    {
        if(is_null($serialized))
        {
            return $this->app->make($class, [null]);
        }

        if(!is_array($serialized))
        {
            return $this->app->make($class, [$serialized]);
        }

        $reflection = new \ReflectionClass($class);
        $parameters = $reflection->getConstructor()->getParameters();

        $deserialized = [];

        foreach($parameters as $parameter)
        {
            $parameter_name = $parameter->getName();
            $parameter_class = $parameter->getClass()->name;

            $deserialized[$parameter_name] = $this->deserialize(
                $parameter_class,
                $serialized[$parameter_name]
            );
        }

        return $reflection->newInstanceArgs($deserialized);
    }
}
