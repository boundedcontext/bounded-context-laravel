<?php namespace BoundedContext\Laravel\Generator;

class DateTime implements \BoundedContext\Contracts\Generator\DateTime
{
    public function now()
    {
        return new \BoundedContext\ValueObject\DateTime();
    }

    public function string($datetime)
    {
        return new \BoundedContext\ValueObject\DateTime($datetime);
    }

    public function generate()
    {
        return $this->now();
    }
}
