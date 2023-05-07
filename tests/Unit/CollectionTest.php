<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use Mediagone\Types\Collections\Errors\EmptyCollectionException;
use Mediagone\Types\Collections\Errors\InvalidCollectionOperationException;
use Mediagone\Types\Collections\Errors\NoPredicateResultException;
use Mediagone\Types\Collections\Errors\TooManyItemsException;
use Mediagone\Types\Collections\Errors\TooManyPredicateResultsException;
use Mediagone\Types\Collections\Typed\MixedCollection;
use Mediagone\Types\Collections\Typed\StringCollection;
use PHPUnit\Framework\TestCase;
use Tests\Mediagone\Types\Collections\Fakes\FakeBar;
use Tests\Mediagone\Types\Collections\Fakes\FakeFoo;
use Tests\Mediagone\Types\Collections\Fakes\FakeFooChild;
use Tests\Mediagone\Types\Collections\Fakes\FakeFooChildCollection;
use Tests\Mediagone\Types\Collections\Fakes\FakeFooCollection;
use Tests\Mediagone\Types\Collections\Fakes\FakeMixedCollection;
use TypeError;
use function iterator_to_array;
use function json_encode;
use function strtolower;


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
    // Conversion methods
    //==================================================================================================================
    
    // toCollection
    
    public function test_can_be_cast_to_another_collection() : void
    {
        $words = FakeMixedCollection::fromArray(['one', 'two', 'three']);
        
        $collection = $words->toCollection(StringCollection::class);
        self::assertInstanceOf(StringCollection::class, $collection);
        self::assertSame(['one', 'two', 'three'], $collection->toArray());
    }
    
    public function test_cannot_be_cast_to_an_inexistant_collection() : void
    {
        $this->expectException(InvalidArgumentException::class);
        
        FakeMixedCollection::fromArray([])->toCollection('Unknown\Collection\Class');
    }
    
    public function test_can_only_be_cast_to_a_class_that_extends_collection() : void
    {
        $this->expectException(TypeError::class);
        
        FakeMixedCollection::fromArray([])->toCollection(FakeFoo::class);
    }
    
    public function test_throws_an_exception_if_invalid_items() : void
    {
        $this->expectException(TypeError::class);
        
        FakeMixedCollection::fromArray(['one', 'two', 3])->toCollection(StringCollection::class);
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
    
    // forEach
    
    public function test_can_execute_a_function_on_each_items() : void
    {
        $items = [];
        $func = static function($item) use(&$items) {
            $items[] = $item;
        };
        
        FakeMixedCollection::fromArray([1, 3, 2, 4])->forEach($func);
        self::assertSame(1, $items[0]);
        self::assertSame(3, $items[1]);
        self::assertSame(2, $items[2]);
        self::assertSame(4, $items[3]);
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
    // Grouping methods tests
    //==================================================================================================================
    
    public function test_can_group_by_specified_key() : void
    {
        // Group values by parity (even/odd)
        $groups = FakeMixedCollection::fromArray([1, 2, 3, 4])->groupBy(fn(int $item) => $item % 2 === 0 ? 'even' : 'odd');
        self::assertSame([1, 3], $groups['odd']->toArray());
        self::assertSame([2, 4], $groups['even']->toArray());
        
        // Group class instances' values by parity (even/odd)
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $groups = FakeFooCollection::fromArray([$foo1, $foo2, $foo3])->groupBy(fn(FakeFoo $item) => $item->getValue() % 2 === 0 ? 'even' : 'odd');
        self::assertSame([$foo1, $foo3], $groups['odd']->toArray());
        self::assertSame([$foo2], $groups['even']->toArray());
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
    
    // remove
    
    public function test_can_remove_an_element() : void
    {
        $foo1 = new FakeFoo();
        $foo2 = new FakeFoo();
        $foo3 = new FakeFoo();
        $foo4 = new FakeFoo();
        $collection = FakeFooCollection::fromArray([$foo1, $foo2, $foo3, $foo4]);
        
        $result = $collection->remove($foo3);
        
        // Collection should be mutable
        self::assertSame($collection, $result);
        
        // Item #3 should have been removed
        self::assertSame([$foo1, $foo2, $foo4], $collection->toArray());
    }
    
    public function test_throw_if_trying_to_remove_an_absent_element() : void
    {
        $this->expectException(LogicException::class);
        
        $collection = FakeFooCollection::fromArray([new FakeFoo(), new FakeFoo()]);
        $result = $collection->remove(new FakeFoo());
    }
    
    // shuffle
    
    public function test_can_shuffle_items() : void
    {
        $items = [
            new FakeFoo('1'),
            new FakeFoo('2'),
            new FakeFoo('3'),
            new FakeFoo('4'),
            new FakeFoo('5'),
            new FakeFoo('6'),
            new FakeFoo('7'),
            new FakeFoo('8'),
            new FakeFoo('9'),
        ];
        $collection = FakeFooCollection::fromArray($items);
        
        // Collection should be mutable and items reordered
        $shuffledCollection = $collection->shuffle();
        self::assertSame($collection, $shuffledCollection);
        self::assertCount(9, $collection->toArray());
        self::assertNotSame($items, $collection->toArray()); // todo?
    }
    
    // reverse
    
    public function test_can_reverse_items() : void
    {
        $items = [1, 2, 3, 4];
        $collection = FakeMixedCollection::fromArray($items);
        
        // Collection should be mutable and items should have been reordered (reverse order)
        $reversedCollection = $collection->reverse();
        self::assertSame($collection, $reversedCollection);
        self::assertSame([4, 3, 2, 1], $reversedCollection->toArray());
    }
    
    // distinct
    
    public function test_can_make_items_distinct_with_primitives() : void
    {
        $items = [1, 4, 2, 2, 3, 4];
        $collection = FakeMixedCollection::fromArray($items);
        
        // Collection should be mutable and contain only unique items (duplicated items are removed)
        $uniqueCollection = $collection->distinct();
        self::assertSame($collection, $uniqueCollection);
        self::assertSame([1, 4, 2, 3], $uniqueCollection->toArray());
    }
    
    public function test_can_make_items_distinct_with_class_instances() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $foo5 = new FakeFoo('2');
        $collection = FakeMixedCollection::fromArray([$foo1, $foo4, $foo2, $foo2, $foo3, $foo4, $foo5]);
        
        // Collection should be mutable and contain only unique instances (duplicated items are removed)
        $uniqueCollection = $collection->distinct();
        self::assertSame($collection, $uniqueCollection);
        self::assertSame([$foo1, $foo4, $foo2, $foo3, $foo5], $uniqueCollection->toArray());
    }
    
    
    // distinctBy
    
    public function test_can_make_items_distinctby_with_primitives() : void
    {
        $items = [1, 2, 3, 4, 5];
        $collection = FakeMixedCollection::fromArray($items);
        
        // Collection should be mutable and contain only unique items by parity (duplicated items are removed)
        $uniqueCollection = $collection->distinctBy(fn(int $item) => $item % 2 === 0);
        self::assertSame($collection, $uniqueCollection);
        self::assertSame([1, 2], $uniqueCollection->toArray());
    }
    
    public function test_can_make_items_distinctby_with_class_instances() : void
    {
        $foo1 = new FakeFoo('1');
        $foo1b = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo2b = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $collection = FakeMixedCollection::fromArray([$foo1, $foo1b, $foo2, $foo2b, $foo3, $foo4]);
       
        // Collection should be mutable and contain only unique items (instance with the same value are removed)
        $uniqueCollection = $collection->distinctBy(fn(FakeFoo $item) => $item->getValue());
        self::assertSame($collection, $uniqueCollection);
        self::assertSame([$foo1, $foo2, $foo3, $foo4], $uniqueCollection->toArray());
    }
    
    // sort
    
    public function test_can_sort_primitive_type_items() : void
    {
        $collection = FakeMixedCollection::fromArray([5, 2, 1, 3, 4]);
        self::assertNotSame([1, 2, 3, 4, 5], $collection->toArray());
        
        // Collection should be mutable and items reordered
        $sortedCollection = $collection->sort();
        self::assertSame($collection, $sortedCollection);
        self::assertSame([1, 2, 3, 4, 5], $sortedCollection->toArray());
    }
    
    // sortDescending
    
    public function test_can_sort_descending_primitive_type_items() : void
    {
        $collection = FakeMixedCollection::fromArray([5, 2, 1, 3, 4]);
        self::assertNotSame([1, 2, 3, 4, 5], $collection->toArray());
        
        // Collection should be mutable and items reordered
        $sortedCollection = $collection->sortDescending();
        self::assertSame($collection, $sortedCollection);
        self::assertSame([5, 4, 3, 2, 1], $sortedCollection->toArray());
    }
    
    // sortBy
    
    public function test_can_sort_items_by_key() : void
    {
        $foo1 = new FakeFoo('1');
        $foo3 = new FakeFoo('3');
        $foo2 = new FakeFoo('2');
        $foo5 = new FakeFoo('5');
        $foo4 = new FakeFoo('4');
        $collection = FakeFooCollection::fromArray([$foo1, $foo5, $foo3, $foo4, $foo2]);
        self::assertSame([$foo1, $foo5, $foo3, $foo4, $foo2], $collection->toArray());
        
        // Collection should be mutable and items reordered
        $sortedCollection = $collection->sortBy(fn(FakeFoo $item) => $item->getValue());
        self::assertSame($collection, $sortedCollection);
        self::assertSame([$foo1, $foo2, $foo3, $foo4, $foo5], $sortedCollection->toArray());
    }
    
    // sortByDescending
    
    public function test_can_sort_items_by_key_descending() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $foo5 = new FakeFoo('5');
        $items = [$foo1, $foo5, $foo3, $foo4, $foo2];
        $collection = FakeFooCollection::fromArray($items);
        self::assertSame([$foo1, $foo5, $foo3, $foo4, $foo2], $collection->toArray());
        
        // Collection should be mutable and items reordered
        $sortedCollection = $collection->sortByDescending(fn(FakeFoo $item) => $item->getValue());
        self::assertSame($collection, $sortedCollection);
        self::assertSame([$foo5, $foo4, $foo3, $foo2, $foo1], $sortedCollection->toArray());
    }
    
    // concat
    
    public function test_can_concatenate_two_collections() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2]);
        $otherCollection = FakeMixedCollection::fromArray([3, 4]);
        
        // Collection should be mutable and items concatenated
        $concatenatedCollection = $collection->concat($otherCollection);
        self::assertSame($collection, $concatenatedCollection);
        self::assertSame([1, 2, 3, 4], $concatenatedCollection->toArray());
    }
    
    public function test_can_concatenate_two_empty_collections() : void
    {
        $collection = FakeMixedCollection::new();
        $otherCollection = FakeMixedCollection::new();
        
        // Collection should be mutable
        $concatenatedCollection = $collection->concat($otherCollection);
        self::assertSame($collection, $concatenatedCollection);
        self::assertSame([], $concatenatedCollection->toArray());
    }
    
    public function test_can_concatenate_two_covariant_collections() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $collection = FakeFooCollection::fromArray([$foo1, $foo2]);
        $fooChild3 = new FakeFooChild('3');
        $fooChild4 = new FakeFooChild('4');
        $covariantCollection = FakeFooChildCollection::fromArray([$fooChild3, $fooChild4]);
        
        // Collection should be mutable
        $concatenatedCollection = $collection->concat($covariantCollection);
        self::assertSame($collection, $concatenatedCollection);
        self::assertSame([$foo1, $foo2, $fooChild3, $fooChild4], $concatenatedCollection->toArray());
    }
    
    public function test_cannot_concatenate_two_contravariant_collections() : void
    {
        $collection = FakeFooChildCollection::fromArray([new FakeFooChild('1'), new FakeFooChild('2')]);
        $contravariantCollection = FakeFooCollection::fromArray([new FakeFoo('3'), new FakeFoo('4')]);
        
        $this->expectException(TypeError::class);
        $collection->concat($contravariantCollection);
    }
    
    public function test_cannot_concatenate_two_collections_of_different_types() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2]);
        $differentCollection = FakeFooCollection::fromArray([new FakeFoo('3'), new FakeFoo('4')]);
        
        $this->expectException(TypeError::class);
        $collection->concat($differentCollection);
    }
    
    
    
    //==================================================================================================================
    // Partitioning methods tests
    //==================================================================================================================
    
    // where
    
    public function test_can_where_items() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        $filteredCollection = $collection->where(fn($item) => $item % 2 === 0);
        
        // Collection should be mutable and contain only even values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([2, 4, 6], $filteredCollection->toArray());
    }
    
    // except
    
    public function test_can_except_items() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5]);
        $other = FakeMixedCollection::fromArray([2, 4, 6]);
        $filteredCollection = $collection->except($other);
       
        // Collection should be mutable and contain only desired values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([1, 3, 5], $filteredCollection->toArray());
    }
    
    public function test_can_except_items_with_class_instances() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $foo5 = new FakeFoo('5');
        $foo5b = new FakeFoo('5');
        $collection = FakeMixedCollection::fromArray([$foo1, $foo2, $foo3, $foo4, $foo5]);
        $other = FakeMixedCollection::fromArray([$foo1, $foo3, $foo5b]);
        $filteredCollection = $collection->except($other);
       
        // Collection should be mutable and contain only desired values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([$foo2, $foo4, $foo5], $filteredCollection->toArray());
    }
    
    // exceptBy
    
    public function test_can_except_items_by_key() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $foo5 = new FakeFoo('5');
        $foo5b = new FakeFoo('5');
        $collection = FakeMixedCollection::fromArray([$foo1, $foo2, $foo3, $foo4, $foo5]);
        $other = FakeMixedCollection::fromArray([$foo1, $foo3, $foo5b]);
        $filteredCollection = $collection->exceptBy($other, fn(FakeFoo $item) => $item->getValue());
        
        // Collection should be mutable and contain only desired values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([$foo2, $foo4], $filteredCollection->toArray());
    }
    
    // intersect
    
    public function test_can_intersect_collections() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5]);
        $other = FakeMixedCollection::fromArray([2, 4, 6]);
        $filteredCollection = $collection->intersect($other);
        
        // Collection should be mutable and contain only desired values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([2, 4], $filteredCollection->toArray());
    }
    
    public function test_can_intersect_collections_with_class_instances() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $foo5 = new FakeFoo('5');
        $foo5b = new FakeFoo('5');
        $collection = FakeMixedCollection::fromArray([$foo1, $foo2, $foo3, $foo4, $foo5]);
        $other = FakeMixedCollection::fromArray([$foo3, $foo5b]);
        $filteredCollection = $collection->intersect($other);
        
        // Collection should be mutable and contain only desired values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([$foo3], $filteredCollection->toArray());
    }
    
    // intersectBy
    
    public function test_can_intersect_collections_by_key() : void
    {
        $foo1 = new FakeFoo('1');
        $foo2 = new FakeFoo('2');
        $foo3 = new FakeFoo('3');
        $foo4 = new FakeFoo('4');
        $foo5 = new FakeFoo('5');
        $foo5b = new FakeFoo('5');
        $collection = FakeMixedCollection::fromArray([$foo1, $foo2, $foo3, $foo4, $foo5]);
        $other = FakeMixedCollection::fromArray([$foo3, $foo5b]);
        $filteredCollection = $collection->intersectBy($other, static fn(FakeFoo $item) => $item->getValue());
        
        // Collection should be mutable and contain only desired values
        self::assertSame($collection, $filteredCollection);
        self::assertSame([$foo3, $foo5], $filteredCollection->toArray());
    }
    
    // skip
    
    public function test_can_skip_items() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Skip first 3 items
        $resultCollection = $collection->skip(3);
        
        // Collection should be mutable and contain only the specified subset of items
        self::assertSame($collection, $resultCollection);
        self::assertSame([4, 5, 6], $resultCollection->toArray());
    }
    
    // skipLast
    
    public function test_can_skip_items_from_end() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Skip last 2 items
        $resultCollection = $collection->skipLast(2);
        
        // Collection should be mutable and contain only the specified subset of items
        self::assertSame($collection, $resultCollection);
        self::assertSame([1, 2, 3, 4], $resultCollection->toArray());
    }
    
    // skipWhile
    
    public function test_can_skip_items_while() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Skip items lesser than 5
        $resultCollection = $collection->skipWhile(fn($item) => $item < 5);
        
        // Collection should be mutable and contain only the specified subset of items
        self::assertSame($collection, $resultCollection);
        self::assertSame([5, 6], $resultCollection->toArray());
    }
    
    // take
    
    public function test_can_take_items() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Collection should be mutable and contain only the specified subset of items
        $resultCollection = $collection->take(3);
        self::assertSame($collection, $resultCollection);
        self::assertSame([1, 2, 3], $resultCollection->toArray());
    }
    
    // takeLast
    
    public function test_can_take_items_from_last() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Collection should be mutable and contain only the specified subset of items
        $resultCollection = $collection->takeLast(2);
        self::assertSame($collection, $resultCollection);
        self::assertSame([5, 6], $resultCollection->toArray());
    }
    
    // takeWhile
    
    public function test_can_take_items_while() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Take items lesser than 5
        $resultCollection = $collection->takeWhile(fn($item) => $item < 5);
        
        // Collection should be mutable and contain only the specified subset of items
        self::assertSame($collection, $resultCollection);
        self::assertSame([1, 2, 3, 4], $resultCollection->toArray());
    }
    
    // skip + take
    
    public function test_can_skip_then_take_items() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        
        // Collection should be mutable and contain only the specified subset of items
        $resultCollection = $collection->skip(1)->take(3);
        self::assertSame($collection, $resultCollection);
        self::assertSame([2, 3, 4], $resultCollection->toArray());
    }
    
    // chunk
    
    public function test_can_chunk_items() : void
    {
        $collection = FakeMixedCollection::fromArray([1, 2, 3, 4, 5, 6]);
        $chunks = $collection->chunk(4);
        
        // Original collection should not have changed
        self::assertSame([1, 2, 3, 4, 5, 6], $collection->toArray());
        
        // Resulting collection should contain chunks of original collection's items
        self::assertCount(2, $chunks);
        self::assertInstanceOf(FakeMixedCollection::class, $chunks[0]);
        self::assertSame([1, 2, 3, 4], $chunks[0]->toArray());
        self::assertInstanceOf(FakeMixedCollection::class, $chunks[1]);
        self::assertSame([5, 6], $chunks[1]->toArray());
    }
    
    
    
    //==================================================================================================================
    // Aggregation methods tests
    //==================================================================================================================
    
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
    
    
    // average
    
    public function test_can_calculate_average() : void
    {
        // With primitive types
        self::assertSame(2., FakeMixedCollection::fromArray([1, 2, 3])->average());
        self::assertSame(2., FakeMixedCollection::fromArray([3, 2, 1])->average());
        self::assertSame(6.5/3, FakeMixedCollection::fromArray([1, 2.5, 3])->average());
        // With class instances and a custom selector function
        $selector = static fn(FakeFoo $foo) => (float)$foo->getValue();
        $items = [new FakeFoo('1'), new FakeFoo('2'), new FakeFoo('3')];
        self::assertSame(2., FakeMixedCollection::fromArray($items)->average($selector));
        $items = [new FakeFoo('1'), new FakeFoo('2.5'), new FakeFoo('3')];
        self::assertSame(6.5/3, FakeMixedCollection::fromArray($items)->average($selector));
    }
    
    public function test_cannot_calculate_average_of_an_empty_collection() : void
    {
        $this->expectException(InvalidCollectionOperationException::class);
        
        FakeMixedCollection::fromArray([])->average();
    }
    
    
    // sum
    
    public function test_can_calculate_sum() : void
    {
        // With primitive types
        self::assertSame(6., FakeMixedCollection::fromArray([1, 2, 3])->sum());
        self::assertSame(6., FakeMixedCollection::fromArray([3, 2, 1])->sum());
        self::assertSame(6.5, FakeMixedCollection::fromArray([1, 2.5, 3])->sum());
        // With class instances and a custom selector function
        $selector = static fn(FakeFoo $foo) => (float)$foo->getValue();
        $items = [new FakeFoo('1'), new FakeFoo('2'), new FakeFoo('3')];
        self::assertSame(6., FakeMixedCollection::fromArray($items)->sum($selector));
        $items = [new FakeFoo('1'), new FakeFoo('2.5'), new FakeFoo('3')];
        self::assertSame(6.5, FakeMixedCollection::fromArray($items)->sum($selector));
    }
    
    // aggregate
    
    public function test_can_aggregate_values() : void
    {
        // With primitive type
        self::assertSame(45, FakeMixedCollection::fromArray([0, 1, 2, 3, 4, 5, 6, 7, 8, 9])->aggregate(0, fn($total, $item) => $total + $item));
        // With class instances and a custom selector function
        $items = [new FakeFoo('1'), new FakeFoo('2'), new FakeFoo('3')];
        self::assertSame(6., FakeMixedCollection::fromArray($items)->sum(static fn(FakeFoo $foo) => (float)$foo->getValue()));
    }
    
    // select
    
    public function test_can_select_items() : void
    {
        $items = [1, 4, 2, 2, 3, 4];
        $collection = FakeMixedCollection::fromArray($items);
        $selectedItems = $collection->select(static fn($item) => $item * 10);
        
        // Collection should be mutable and contain only unique items
        self::assertSame([10, 40, 20, 20, 30, 40], $selectedItems->toArray());
    }
    
    
    
    //==================================================================================================================
    // Query methods
    //==================================================================================================================
    
    // join
    
    public function test_can_join_items() : void
    {
        $words = FakeMixedCollection::fromArray(['one', 'two', 'three', 'Three']);
        $translations = FakeMixedCollection::fromArray([
            (object)['word' => 'one', 'value' => 'un'],
            (object)['word' => 'two', 'value' => 'deux'],
            (object)['word' => 'two', 'value' => 'dos'],
            (object)['word' => 'three', 'value' => 'trois'],
            (object)['word' => 'Three', 'value' => 'Trois'],
        ]);
        
        // Using the default equality comparer.
        $results = $words->join(
            $translations,
            static fn($item) => $item,
            static fn($item) => $item->word,
            static fn($word, $translation) => (object)['word' => $word, 'value' => $translation->value]
        );
        self::assertCount(5, $results);
        self::assertInstanceOf(MixedCollection::class, $results);
        self::assertSame(['word' => 'one', 'value' => 'un'], (array)$results[0]);
        self::assertSame(['word' => 'two', 'value' => 'deux'], (array)$results[1]);
        self::assertSame(['word' => 'two', 'value' => 'dos'], (array)$results[2]);
        self::assertSame(['word' => 'three', 'value' => 'trois'], (array)$results[3]);
        self::assertSame(['word' => 'Three', 'value' => 'Trois'], (array)$results[4]);
        
        // Using a custom equality comparer.
        $results = $words->join(
            $translations,
            static fn($item) => strtolower($item),
            static fn($item) => strtolower($item->word),
            static fn($word, $translation) => (object)['word' => $word, 'value' => $translation->value]
        );
        self::assertCount(7, $results);
        self::assertInstanceOf(MixedCollection::class, $results);
        self::assertSame(['word' => 'one', 'value' => 'un'], (array)$results[0]);
        self::assertSame(['word' => 'two', 'value' => 'deux'], (array)$results[1]);
        self::assertSame(['word' => 'two', 'value' => 'dos'], (array)$results[2]);
        self::assertSame(['word' => 'three', 'value' => 'trois'], (array)$results[3]);
        self::assertSame(['word' => 'three', 'value' => 'Trois'], (array)$results[4]);
        self::assertSame(['word' => 'Three', 'value' => 'trois'], (array)$results[5]);
        self::assertSame(['word' => 'Three', 'value' => 'Trois'], (array)$results[6]);
    }
    
    
    
    //==================================================================================================================
    // Quantifier methods tests
    //==================================================================================================================
    
    // all
    
    public function test_if_all_elements_satisfy_a_condition() : void
    {
        // All elements satisfy the condition
        self::assertTrue(FakeMixedCollection::fromArray([1, 2, 3])->all(fn($item) => $item > 0));
        self::assertTrue(FakeMixedCollection::fromArray([1, 2, 3])->all(fn($item) => $item < 4));
        self::assertTrue(FakeMixedCollection::fromArray([1, 1, 1])->all(fn($item) => $item === 1));
        // Not all elements satisfy the condition
        self::assertFalse(FakeMixedCollection::fromArray([1, 2, 3])->all(fn($item) => $item > 1));
        self::assertFalse(FakeMixedCollection::fromArray([1, 2, 3])->all(fn($item) => $item > 2));
        self::assertFalse(FakeMixedCollection::fromArray([1, 2, 3])->all(fn($item) => $item > 3));
        self::assertFalse(FakeMixedCollection::fromArray([1, 1, 2])->all(fn($item) => $item === 1));
    }
    
    // any
    
    public function test_if_any_element_satisfies_a_condition() : void
    {
        // Without custom predicate function (check fullness)
        self::assertTrue(FakeMixedCollection::fromArray([1, 2, 3])->any());
        self::assertFalse(FakeMixedCollection::fromArray([])->any());
        // With custom predicate function (all elements satisfy the condition)
        $collection = FakeMixedCollection::fromArray([1, 2, 3]);
        self::assertTrue($collection->any(fn($item) => $item < 2));
        self::assertTrue($collection->any(fn($item) => $item < 3));
        self::assertTrue($collection->any(fn($item) => $item < 4));
        // With custom predicate function (no element satisfies the condition)
        $collection = FakeMixedCollection::fromArray([1, 2, 3]);
        self::assertFalse($collection->any(fn($item) => $item < 0));
        self::assertFalse($collection->any(fn($item) => $item > 3));
    }
    
    // contains
    
    public function test_if_contains_a_value() : void
    {
        // Primitive types
        self::assertTrue(FakeMixedCollection::fromArray([1, 2, 3])->contains(2));
        self::assertFalse(FakeMixedCollection::fromArray([1, 2, 3])->contains(4));
        self::assertTrue(FakeMixedCollection::fromArray(['a', 'b', 'c'])->contains('b'));
        self::assertFalse(FakeMixedCollection::fromArray(['a', 'b', 'c'])->contains('d'));
        // Class instances
        $foo = new FakeFoo();
        self::assertTrue(FakeMixedCollection::fromArray([new FakeFoo(), $foo])->contains($foo));
        self::assertFalse(FakeMixedCollection::fromArray([new FakeFoo(), new FakeFoo()])->contains(new FakeFoo()));
    }
    
    public function test_if_contains_using_custom_equality_comparer() : void
    {
        $items = [new FakeFoo('A'), new FakeFoo('B')];
        // Different class instances are not equal by default...
        self::assertFalse(FakeMixedCollection::fromArray($items)->contains(
            new FakeFoo('B')
        ));
        // ...but a custom comparer can compare instance's value instead
        $comparer = static fn (FakeFoo $foo, string $needle) => $foo->getValue() === $needle;
        self::assertTrue(FakeMixedCollection::fromArray($items)->contains('B', $comparer));
        self::assertFalse(FakeMixedCollection::fromArray($items)->contains('C', $comparer));
    }
    
    
    
    //==================================================================================================================
    // Misc
    //==================================================================================================================
    
    
    
    public function test_can_chain_many_operations() : void
    {
        $items = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $collection = FakeMixedCollection::fromArray($items);
        $resultCollection = $collection
            ->where(fn($item) => $item > 1)
            ->append(1)
            ->select(static fn($item) => $item * 10);
        
        self::assertNotSame($collection, $resultCollection);
        self::assertSame([20, 30, 40, 50, 60, 70, 80, 90, 10], $resultCollection->toArray());
    }
    
}
