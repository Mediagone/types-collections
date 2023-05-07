<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use Mediagone\Types\Collections\Typed\BoolCollection;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Typed\BoolCollection
 */
final class BoolCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = BoolCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $collection = BoolCollection::fromArray([true, true, false]);
        self::assertSame([true, true, false], $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(InvalidCollectionItemException::class);
        $this->expectExceptionMessage((new InvalidCollectionItemException(0, 'boolean', 2))->getMessage());
    
        BoolCollection::fromArray([true, true, 0]);
    }
}
