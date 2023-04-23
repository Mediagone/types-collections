<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Tests\Mediagone\Types\Collections\Fakes\FakeBar;
use Tests\Mediagone\Types\Collections\Fakes\FakeFoo;
use function iterator_to_array;
use function json_encode;


/**
 * @covers \Mediagone\Types\Collections\Collection
 */
final class CollectionTest extends TestCase
{
    //==================================================================================================================
    // Constructors tests
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = FakeFooCollection::new();
        self::assertSame([], $collection->toArray());
    }
    
    
    public function test_can_be_created_from_array() : void
    {
        $items = [new FakeFoo(), new FakeFoo(), new FakeFoo()];
        $collection = FakeFooCollection::fromArray($items);
        
        self::assertSame($items, $collection->toArray());
    }
    
    
    
    //==================================================================================================================
    // Interfaces implementation tests
    //==================================================================================================================
    
    public function test_implements_IteratorAggregate() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 3, 2]);
        
        self::assertInstanceOf(IteratorAggregate::class, $collection);
        self::assertSame([1, 3, 2], iterator_to_array($collection->getIterator()));
    }
    
    public function test_implements_JsonSerializable() : void
    {
        $collection = FakeMixedCollection::fromArray([false, true, '2', 3, 4.56]);
        
        self::assertInstanceOf(JsonSerializable::class, $collection);
        self::assertSame('[false,true,"2",3,4.56]', json_encode($collection, JSON_THROW_ON_ERROR));
    }
    
    
    public function test_implements_Countable() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2]);
        
        self::assertInstanceOf(Countable::class, $collection);
        self::assertCount(2, $collection);
    }
    
    
    public function test_implements_ArrayAccess() : void
    {
        $foo1 = new FakeFoo();
        $foo2 = new FakeFoo();
        $collection = FakeFooCollection::fromArray([$foo1, $foo2]);
        
        self::assertInstanceOf(ArrayAccess::class, $collection);
        
        self::assertTrue(isset($collection[0]));
        self::assertSame($foo1, $collection[0]);
        
        self::assertTrue(isset($collection[1]));
        self::assertSame($foo2, $collection[1]);
        
        self::assertFalse(isset($collection[2]));
    }
    
    
    public function test_cannot_use_ArrayAccess_offsetSet() : void
    {
        $this->expectException(BadMethodCallException::class);
        
        $collection = FakeMixedCollection::fromArray([1, 2]);
        $collection[2] = 3;
    }
    
    
    public function test_cannot_use_ArrayAccess_offsetUnset() : void
    {
        $this->expectException(BadMethodCallException::class);
        
        $collection = FakeMixedCollection::fromArray([1, 2]);
        unset($collection[0]);
    }
    
    
    
}
