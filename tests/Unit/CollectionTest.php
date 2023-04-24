<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Mediagone\Types\Collections\Errors\EmptyCollectionException;
use Mediagone\Types\Collections\Errors\NoPredicateResultException;
use Mediagone\Types\Collections\Errors\TooManyItemsException;
use Mediagone\Types\Collections\Errors\TooManyPredicateResultsException;
use PHPUnit\Framework\TestCase;
use Tests\Mediagone\Types\Collections\Fakes\FakeBar;
use Tests\Mediagone\Types\Collections\Fakes\FakeFoo;
use Tests\Mediagone\Types\Collections\Fakes\FakeFooCollection;
use Tests\Mediagone\Types\Collections\Fakes\FakeMixedCollection;
use TypeError;
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
    // Element operations tests
    //==================================================================================================================
    
    // first
    
    public function test_can_return_first_element() : void
    {
        // Without custom predicate function
        self::assertSame(1, FakeMixedCollection::fromArray([1, 2, 3])->first());
        // With custom predicate function
        self::assertSame(1, FakeMixedCollection::fromArray([1, 2, 3])->first(fn($e) => $e < 3));
        self::assertSame(2, FakeMixedCollection::fromArray([1, 2, 3])->first(fn($e) => $e > 1));
    }
    
    public function test_throws_exception_getting_first_element_of_an_empty_collection() : void
    {
        $this->expectException(EmptyCollectionException::class);
        
        FakeMixedCollection::fromArray([])->first();
    }
    
    public function test_throws_exception_getting_first_element_from_empty_predicate_result() : void
    {
        $this->expectException(NoPredicateResultException::class);
        FakeMixedCollection::fromArray([1, 2, 3])->first(fn($e) => $e === 4);
    }
    
    // firstOrDefault
    
    public function test_can_return_default_as_first_element() : void
    {
        // Without custom predicate function
        self::assertSame('default', FakeMixedCollection::fromArray([])->firstOrDefault('default'));
        // With custom predicate function
        self::assertSame('default', FakeMixedCollection::fromArray([1, 2, 3])->firstOrDefault('default', fn($e) => $e > 3));
        self::assertSame('default', FakeMixedCollection::fromArray([1, 2, 3])->firstOrDefault('default', fn($e) => $e < 1));
    }
    
    // last
    
    public function test_can_return_last_element() : void
    {
        // Without custom predicate function
        self::assertSame(3, FakeMixedCollection::fromArray([1, 2, 3])->last());
        // With custom predicate function
        self::assertSame(2, FakeMixedCollection::fromArray([1, 2, 3])->last(fn($e) => $e < 3));
        self::assertSame(2, FakeMixedCollection::fromArray([1, 2, 3])->last(fn($e) => $e !== 3));
    }
    
    public function test_throws_exception_getting_last_element_of_an_empty_collection() : void
    {
        $this->expectException(EmptyCollectionException::class);
        FakeMixedCollection::fromArray([])->last();
    }
    
    public function test_throws_exception_getting_last_element_from_empty_predicate_result() : void
    {
        $this->expectException(NoPredicateResultException::class);
        FakeMixedCollection::fromArray([1, 2, 3])->last(fn($e) => $e === 4);
    }
    
    // lastOrDefault
    
    public function test_can_return_default_as_last_element() : void
    {
        // With default value
        self::assertSame('default', FakeMixedCollection::fromArray([])->lastOrDefault('default'));
        // Without custom predicate function
        self::assertSame('default', FakeMixedCollection::fromArray([1, 2, 3])->lastOrDefault('default', fn($e) => $e > 3));
        self::assertSame('default', FakeMixedCollection::fromArray([1, 2, 3])->lastOrDefault('default', fn($e) => $e < 1));
    }
    
    // single
    
    public function test_can_return_a_single_element() : void
    {
        // With only one item and no predicate function
        self::assertSame(1, FakeMixedCollection::fromArray([1])->single());
        // With primitive type and custom predicate function
        self::assertSame(2, FakeMixedCollection::fromArray([1, 1, 2, 3, 3])->single(fn($e) => $e === 2));
        // With class instances and custom predicate function
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        self::assertSame($foo2, FakeMixedCollection::fromArray([$foo1, $foo1, $foo2, $foo3, $foo3])->single(fn(FakeFoo $e) => $e->getValue() === '2'));
    }
    
    public function test_throws_exception_trying_to_return_a_single_element_of_an_empty_collection() : void
    {
        $this->expectException(EmptyCollectionException::class);
        
        FakeMixedCollection::new()->single();
    }
    
    public function test_throws_exception_trying_to_return_a_single_element_with_no_predicate_and_too_many_collection_items() : void
    {
        $this->expectException(TooManyItemsException::class);
        
        FakeMixedCollection::fromArray([1, 2, 3, 1])->single();
    }
    
    public function test_throws_exception_trying_to_return_a_single_element_with_no_results() : void
    {
        $this->expectException(NoPredicateResultException::class);
        
        FakeMixedCollection::fromArray([1, 2, 3])->single(fn(int $e) => $e === 4);
    }
    
    public function test_throws_exception_trying_to_return_a_single_element_with_too_many_predicate_results() : void
    {
        $this->expectException(TooManyPredicateResultsException::class);
        
        FakeMixedCollection::fromArray([1, 2, 3, 1])->single(fn(int $e) => $e === 1);
    }
    
    // singleOrDefault
    
    public function test_can_return_default_as_single_element() : void
    {
        // With only one item and no predicate function
        self::assertSame(1, FakeMixedCollection::fromArray([1])->singleOrDefault(0));
        // With predicate function filtering out all collection items except one
        self::assertSame(1, FakeMixedCollection::fromArray([1])->singleOrDefault(0, fn($e) => $e === 1));
        // With predicate function filtering out all collection items except one
        self::assertSame(3, FakeMixedCollection::fromArray([1, 2, 3])->singleOrDefault(0, fn($e) => $e === 3));
        // With empty collection
        self::assertSame(0, FakeMixedCollection::fromArray([])->singleOrDefault(0));
        // With predicate function filtering out all collection items
        self::assertSame(0, FakeMixedCollection::fromArray([1, 2, 3])->singleOrDefault(0, fn($e) => $e > 3));
    }
    
    public function test_throws_exception_trying_to_return_default_as_single_element_with_too_many_collection_items() : void
    {
        $this->expectException(TooManyItemsException::class);
        
        FakeMixedCollection::fromArray([1, 2, 3, 1])->singleOrDefault(0);
    }
    
    public function test_throws_exception_trying_to_return_default_as_single_element_with_too_many_predicate_results() : void
    {
        $this->expectException(TooManyPredicateResultsException::class);
        
        FakeMixedCollection::fromArray([1, 2, 3, 1])->singleOrDefault(0, fn($e) => $e === 1);
    }
    
    // min
    
    public function test_can_return_min_element() : void
    {
        // Without custom selector function
        self::assertSame(1, FakeMixedCollection::fromArray([3, 2, 1])->min());
        // With custom selector function
        self::assertSame(-3, FakeMixedCollection::fromArray([1, 3, 2])->min(fn($e) => -$e));
    }
    
    public function test_throws_exception_trying_to_return_min_element_of_an_empty_collection() : void
    {
        $this->expectException(EmptyCollectionException::class);
        
        FakeMixedCollection::fromArray([])->min();
    }
    
    // max
    
    public function test_can_return_max_element() : void
    {
        // Without custom selector function
        self::assertSame(3, FakeMixedCollection::fromArray([1, 3, 2])->max());
        // With custom selector function
        self::assertSame(-1, FakeMixedCollection::fromArray([1, 3, 2])->max(fn($e) => -$e));
    }
    
    public function test_throws_exception_trying_to_return_max_element_of_an_empty_collection() : void
    {
        $this->expectException(EmptyCollectionException::class);
        
        FakeMixedCollection::fromArray([])->max();
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
    
    
    
    
    
    
    //==================================================================================================================
    // Mutation methods tests
    //==================================================================================================================
    
    // append
    
    public function test_can_append() : void
    {
        $items = [new FakeFoo(), new FakeFoo(), new FakeFoo()];
        $collection = FakeFooCollection::fromArray($items);
        
        $newItem = new FakeFoo();
        $result = $collection->append($newItem);
    
        self::assertSame([...$items, $newItem], $collection->toArray());
        self::assertSame($collection, $result); // Collection should be mutable
    }
    
    public function test_cannot_append_element_of_invalid_type() : void
    {
        $this->expectException(TypeError::class);
        FakeFooCollection::new()->append(new FakeBar());
    }
    
    // prepend
    
    public function test_can_prepend() : void
    {
        $items = [new FakeFoo(), new FakeFoo(), new FakeFoo()];
        $collection = FakeFooCollection::fromArray($items);
        
        $newItem = new FakeFoo();
        $result = $collection->prepend($newItem);
        
        self::assertSame([$newItem, ...$items], $collection->toArray());
        self::assertSame($collection, $result); // Collection should be mutable
    }
    
    public function test_cannot_prepend_element_of_invalid_type() : void
    {
        $this->expectException(TypeError::class);
        FakeFooCollection::new()->prepend(new FakeBar());
    }
    
}
