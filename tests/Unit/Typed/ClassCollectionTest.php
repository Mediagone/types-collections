<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use InvalidArgumentException;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use PHPUnit\Framework\TestCase;
use Tests\Mediagone\Types\Collections\Fakes\FakeBar;
use Tests\Mediagone\Types\Collections\Fakes\FakeFoo;
use Tests\Mediagone\Types\Collections\Fakes\FakeFooClassCollection;
use Tests\Mediagone\Types\Collections\Fakes\FakeUnknownClassCollection;


/**
 * @covers \Mediagone\Types\Collections\Typed\ClassCollection
 */
final class ClassCollectionTest extends TestCase
{
    //==================================================================================================================
    // Construct empty tests
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = FakeFooClassCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_cannot_be_created_empty_of_inexistant_class() : void
    {
        $this->expectException(InvalidArgumentException::class);
        
        FakeUnknownClassCollection::new();
    }
    
    
    
    //==================================================================================================================
    // Construct from array tests
    //==================================================================================================================
    
    public function test_can_be_created_from_array() : void
    {
        $foo1 = new FakeFoo();
        $foo2 = new FakeFoo();
        $foo3 = new FakeFoo();
        $collection = FakeFooClassCollection::fromArray([$foo1, $foo2, $foo3]);
        self::assertSame([$foo1, $foo2, $foo3], $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(InvalidCollectionItemException::class);
        
        FakeFooClassCollection::fromArray([new FakeFoo(), new FakeFoo(), 1]);
    }
    
    public function test_cannot_be_created_from_array_with_invalid_class_instance() : void
    {
        $this->expectException(InvalidCollectionItemException::class);
        
        FakeFooClassCollection::fromArray([new FakeFoo(), new FakeFoo(), new FakeBar()]);
    }
    
    public function test_cannot_be_created_from_array_of_unknown_class() : void
    {
        $this->expectException(InvalidArgumentException::class);
        
        FakeUnknownClassCollection::fromArray([]);
    }
}
