<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Typed\MixedCollection;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Typed\MixedCollection
 */
final class MixedCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = MixedCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $arr = [1, 1.234, '5', true, [], (object)[], 'is_string'];
        $collection = MixedCollection::fromArray($arr);
        self::assertSame($arr, $collection->toArray());
    }
}
