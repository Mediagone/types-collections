<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use Mediagone\Types\Collections\Typed\ArrayCollection;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Typed\ArrayCollection
 */
final class ArrayCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = ArrayCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $collection = ArrayCollection::fromArray([[1], [2], [3]]);
        self::assertSame([[1], [2], [3]], $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(InvalidCollectionItemException::class);
        $this->expectExceptionMessage((new InvalidCollectionItemException(3, 'array', 2))->getMessage());
    
        ArrayCollection::fromArray([[1], [2], 3]);
    }
}
