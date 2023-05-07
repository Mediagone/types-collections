<?php

namespace Tests\Mediagone\Types\Collections\Fakes;

use Mediagone\Types\Collections\Collection;


final class FakeMixedCollection extends Collection
{
    public static function new(?callable $validator = null) : self
    {
        return self::fromArray([], $validator);
    }
    
    public static function fromArray(array $items, ?callable $validator = null) : self
    {
        return new self($items, $validator);
    }
}
