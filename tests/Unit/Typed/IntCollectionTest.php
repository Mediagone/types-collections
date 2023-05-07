<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Typed\IntCollection;
use PHPUnit\Framework\TestCase;
use TypeError;


/**
 * @covers \Mediagone\Types\Collections\Typed\IntCollection
 */
final class IntCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = IntCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_integer_range() : void
    {
        $collection = IntCollection::fromRange(1, 4);
        self::assertSame([1, 2, 3, 4], $collection->toArray());
    }
    
    public function test_can_be_created_from_integer_range_with_custom_step() : void
    {
        $collection = IntCollection::fromRange(1, 6, 2);
        self::assertSame([1, 3, 5], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $collection = IntCollection::fromArray([1, 3, 2]);
        self::assertSame([1, 3, 2], $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(TypeError::class);
        
        IntCollection::fromArray([1, '3', 2]);
    }
}
