<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use Mediagone\Types\Collections\Typed\ResourceCollection;
use PHPUnit\Framework\TestCase;
use function fopen;


/**
 * @covers \Mediagone\Types\Collections\Typed\ResourceCollection
 */
final class ResourceCollectionTest extends TestCase
{
    //==================================================================================================================
    // Constructors tests
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = ResourceCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    
    public function test_can_be_created_from_array() : void
    {
        $items = [fopen(__FILE__, 'rb')];
        $collection = ResourceCollection::fromArray($items);
        self::assertSame($items, $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(InvalidCollectionItemException::class);
        
        ResourceCollection::fromArray([fopen(__FILE__, 'rb'), 'not a resource']);
    }
}
