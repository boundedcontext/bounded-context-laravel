<?php namespace BoundedContext\Laravel\ValueObject;

use BoundedContext\ValueObject\AbstractValueObject;
use Rhumsaa\Uuid\Uuid as RhumsaaUuid;
use BoundedContext\Contracts\ValueObject\Identifier;

class Uuid extends AbstractValueObject implements Identifier
{
    private $uuid;

    public function __construct($uuid)
    {
        $this->uuid = RhumsaaUuid::fromString($uuid);
    }

    public function is_null()
    {
        return $this->equals(new Uuid('00000000-0000-0000-0000-000000000000'));
    }

    public function serialize()
    {
        return $this->uuid->toString();
    }

    public static function deserialize($uuid = null)
    {
        return new Uuid($uuid);
    }
}
