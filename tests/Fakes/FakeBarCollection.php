<?php

namespace Tests\Mediagone\Types\Collections\Fakes;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_a;


/*
 * @extends Collection<FakeBar>
 */
final class FakeBarCollection extends Collection
{
    protected function getValidator(): ?Closure
    {
        // Ensure that all elements in the supplied array are FakeBar instances.
        return static function($item, $index) {
            if (! is_a($item, FakeBar::class)) {
                throw new InvalidCollectionItemException($item, FakeBar::class, $index);
            }
        };
    }
    
    protected static function classFqcn() : string
    {
        return FakeBar::class;
    }
}
