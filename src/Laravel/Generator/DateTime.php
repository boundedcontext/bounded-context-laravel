<?php namespace BoundedContext\Laravel\Generator;

class DateTime implements \BoundedContext\Contracts\Generator\DateTime
{
    public function now()
    {
        return new DateTime();
    }

    public function string($datetime)
    {
        return new DateTime($datetime);
    }

    public function generate()
    {
        return $this->now();
    }
}
