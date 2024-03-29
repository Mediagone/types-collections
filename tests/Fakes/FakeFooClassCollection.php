<?php

namespace Tests\Mediagone\Types\Collections\Fakes;

use Mediagone\Types\Collections\Typed\ClassCollection;
use TypeError;
use function gettype;
use function is_a;
use function is_object;


/*
 * @extends ClassCollection<Foo>
 */
class FakeFooClassCollection extends ClassCollection
{
    protected static function classFqcn() : string
    {
        return FakeFoo::class;
    }
}
