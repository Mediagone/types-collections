<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Typed;

use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Typed\FloatCollection;
use PHPUnit\Framework\TestCase;
use TypeError;


/**
 * @covers \Mediagone\Types\Collections\Typed\FloatCollection
 */
final class FloatCollectionTest extends TestCase
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    public function test_can_be_created_empty() : void
    {
        $collection = FloatCollection::new();
        self::assertInstanceOf(Collection::class, $collection);
        self::assertSame([], $collection->toArray());
    }
    
    public function test_can_be_created_from_array() : void
    {
        $collection = FloatCollection::fromArray([1., 3., 2.]);
        self::assertSame([1., 3., 2.], $collection->toArray());
    }
    
    public function test_can_be_created_from_array_with_invalid_element() : void
    {
        $this->expectException(TypeError::class);
        
        FloatCollection::fromArray([1., 3., 2]);
        // self::assertSame([1, 3, 2], $collection->toArray());
    }
    
    // public function test_can_be_created_from_integer_range() : void
    // {
    //     $collection = FloatCollection::fromRange(1, 4);
    //     self::assertSame([1, 2, 3, 4], $collection->toArray());
    // }
    
    // public function test_can_be_created_from_integer_range_with_custom_step() : void
    // {
    //     $collection = FloatCollection::fromRange(1, 6, 2);
    //     self::assertSame([1, 3, 5], $collection->toArray());
    // }
    
    
    // public function test_can_be_created_from_integer_range() : void
    // {
    //     $collection = Collection::fromRangeInt(1, 4);
    //     self::assertSame([1, 2, 3, 4], $collection->toArray());
    // }
    
    // public function test_can_be_created_from_integer_range_with_custom_step() : void
    // {
    //     $collection = Collection::fromRangeInt(1, 6, 2);
    //     self::assertSame([1, 3, 5], $collection->toArray());
    // }
    
    // public function test_can_be_created_from_float_range() : void
    // {
    //     $collection = Collection::fromRangeFloat(1, 2);
    //     self::assertSame([1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.0], $collection->toArray());
    // }
    
    // public function test_can_be_created_from_float_range_with_custom_step() : void
    // {
    //     $collection = Collection::fromRangeFloat(1, 2, 0.3);
    //     self::assertSame([1.0, 1.3, 1.6, 1.9], $collection->toArray());
    // }
    
    // public function test_can_be_created_from_array_with_custom_validator() : void
    // {
    //     $arr = [1, 3, 2];
    //    
    //     $count = 0;
    //     $validator = static function($element) use (&$count) {
    //         $count++;
    //     };
    //     $collection = ClassCollection::fromArray($arr, $validator);
    //    
    //     self::assertSame(3, $count);
    //     self::assertSame($arr, $collection->toArray());
    // }
    
    // public function test_can_be_created_from_array_with_custom_validator_and_invalid_element() : void
    // {
    //     $this->expectException(InvalidArgumentException::class);
    //    
    //     $arr = [1, 3, 2, 4];
    //     $validator = static function($element) {
    //         if ($element >= 4) {
    //             throw new InvalidArgumentException('Collection elements must be lesser than 4 (got '.$element.')');
    //         }
    //     };
    //    
    //     ClassCollection::fromArray($arr, $validator);
    // }
}
