<?php

namespace Tests\Mediagone\Types\Collections\Fakes;

use Mediagone\Types\Collections\Typed\ClassCollection;


final class FakeUnknownClassCollection extends ClassCollection
{
    protected static function classFqcn() : string
    {
        return 'Unknown\Class\Foo';
    }
}
