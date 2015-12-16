<?php namespace BoundedContext\Laravel\ValueObject;

use BoundedContext\ValueObject\AbstractValueObject;
use BoundedContext\Contracts\ValueObject\Identifier;

class NamespaceIdentifier extends AbstractValueObject implements Identifier
{
    private $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    public function is_null()
    {
        return false;
    }

    public function serialize()
    {
        return $this->namespace;
    }

    public static function deserialize($namespace = null)
    {
        return new NamespaceIdentifier($namespace);
    }
}
