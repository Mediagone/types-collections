<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Typed\CallableCollection;
use PHPUnit\Framework\TestCase;
use TypeError;


/**
 * @covers \Mediagone\Types\Collections\Typed\CallableCollection
 */
final class CallableCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = CallableCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $items = ['is_string', fn() => 2, function() { return 3; }];
        $collection = CallableCollection::fromArray($items);
        self::assertSame($items, $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(TypeError::class);
    
        CallableCollection::fromArray(['is_string', fn() => 2, 3]);
    }
}
