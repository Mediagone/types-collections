<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Typed\StringCollection;
use PHPUnit\Framework\TestCase;
use TypeError;


/**
 * @covers \Mediagone\Types\Collections\Typed\StringCollection
 */
final class StringCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = StringCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_string_range() : void
    {
        $collection = StringCollection::fromRange('a', 'h');
        self::assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'], $collection->toArray());
    }
    
    public function test_can_be_created_from_integer_range_with_custom_step() : void
    {
        $collection = StringCollection::fromRange('a', 'h', 2);
        self::assertSame(['a','c', 'e', 'g'], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $collection = StringCollection::fromArray(['1', '3', '2']);
        self::assertSame(['1', '3', '2'], $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(TypeError::class);
    
        StringCollection::fromArray(['1', '3', 2]);
    }
}
