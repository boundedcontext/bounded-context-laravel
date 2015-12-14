<?php namespace BoundedContext\Laravel\ValueObject;

use BoundedContext\Contracts\ValueObject\ValueObject;
use Rhumsaa\Uuid\Uuid as RhumsaaUuid;
use BoundedContext\Contracts\ValueObject\Identifier;

class Uuid implements Identifier
{
    private $uuid;

    public function __construct($uuid)
    {
        $this->uuid = RhumsaaUuid::fromString($uuid);
    }

    public function equals(ValueObject $other)
    {
        return ($this->serialize() == $other->serialize());
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
