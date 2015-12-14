<?php namespace BoundedContext\Laravel\Generator;

use Rhumsaa\Uuid\Uuid as RhumsaaUuid;

class Uuid implements \BoundedContext\Contracts\Generator\Identifier
{
    public function generate()
    {
        return new \BoundedContext\Laravel\ValueObject\Uuid(
            RhumsaaUuid::uuid4()
        );
    }

    public function null()
    {
        return new \BoundedContext\Laravel\ValueObject\Uuid(
            '00000000-0000-0000-0000-000000000000'
        );
    }

    public function string($identifier)
    {
        return new \BoundedContext\Laravel\ValueObject\Uuid($identifier);
    }
}
