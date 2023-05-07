<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Typed\ObjectCollection;
use PHPUnit\Framework\TestCase;
use TypeError;


/**
 * @covers \Mediagone\Types\Collections\Typed\ObjectCollection
 */
final class ObjectCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = ObjectCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $items = [(object)[1], (object)[2], (object)[3]];
        $collection = ObjectCollection::fromArray($items);
        self::assertSame($items, $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(TypeError::class);
    
        ObjectCollection::fromArray([(object)[1], (object)[2], 3]);
    }
}
