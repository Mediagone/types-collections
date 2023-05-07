<?php

namespace Tests\Mediagone\Types\Collections\Fakes;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_a;


/*
 * @extends Collection<FakeFoo>
 */
class FakeFooCollection extends Collection
{
    protected function getValidator(): ?Closure
    {
        // Ensure that all elements in the supplied array are Foo instances.
        return static function($item, $index) {
            if (! is_a($item, FakeFoo::class)) {
                throw new InvalidCollectionItemException($item, FakeFoo::class, $index);
            }
        };
    }

    // public static function new() : self
    // {
    //     return self::fromArray([]);
    // }
    
    // /**
    //  * @param FakeFoo[] $items
    //  */
    // public static function fromArray(array $items) : self
    // {
    //     return new self($items);
    // }
}
