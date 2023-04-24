<?php declare(strict_types=1);

namespace Mediagone\Types\Collections;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Mediagone\Types\Collections\Errors\EmptyCollectionException;
use Mediagone\Types\Collections\Errors\NoPredicateResultException;
use Mediagone\Types\Collections\Errors\TooManyItemsException;
use Mediagone\Types\Collections\Errors\TooManyPredicateResultsException;
use function array_chunk;
use function array_filter;
use function array_map;
use function array_reverse;
use function array_slice;
use function array_unshift;
use function array_values;
use function count;
use function end;
use function max;
use function min;
use function shuffle;


/**
 * An abstract collection class, that provides chainable methods to perform traversal, filter and projection operations.
 * Strong-typing or value constraints can be enforced in subclasses using an optional validator.
 * @template T The type of the items in the collection.
 * @implements ArrayAccess<int,T>
 * @implements IteratorAggregate<int, T>
 */
abstract class Collection implements Countable, IteratorAggregate, ArrayAccess, JsonSerializable
{
    //==================================================================================================================
    // Overloadable methods
    //==================================================================================================================
    
    /**
     * Returns a validation function to perform custom checks on the collection's values, or null to ignore.
     * @return ?Closure(T $item, ?int $index, static $collection):void $validator A validation function invoked on each item to check its validity, throw exceptions inside to notify any error.
     */
    protected function getValidator(): ?Closure
    {
        return null;
    }
    
    
    /**
     * Returns the current or a new collection instance to modify when calling transform methods, to define if class is immutable.
     * @return static
     */
    protected function getModifiableInstance()
    {
        return $this;
    }
    
    
    
    //==================================================================================================================
    // Properties
    //==================================================================================================================
    
    /** @var T[] */
    protected array $items;
    
    protected ?Closure $validator;
    
    
    
    //==================================================================================================================
    // Constructors
    //==================================================================================================================
    
    /**
     * Instantiates a new collection instance.
     * @param T[] $items The initial collection's items.
     */
    final protected function __construct(array $items)
    {
        $validator = $this->getValidator();
        if ($validator !== null) {
            foreach ($items as $index => $item) {
                $validator($item, $index, $this);
            }
        }
        
        $this->items = array_values($items);
        $this->validator = $validator ? Closure::fromCallable($validator) : null;
    }
    
    /**
     * Generates a new empty boolean collection.
     * @return static
     */
    public static function new()
    {
        return static::fromArray([]);
    }
    
    
    /**
     * Generates a new collection containing the specified items.
     * @param T[] $items
     * @return static
     */
    public static function fromArray(array $items)
    {
        return new static($items);
    }
    
    
    
    //==================================================================================================================
    // Conversion operations
    //==================================================================================================================
    
    /**
     * Return the collection's items as an array.
     * @return T[]
     */
    final public function toArray() : array
    {
        return $this->items;
    }
    
    
    
    //==================================================================================================================
    // Countable interface implementation
    //==================================================================================================================
    
    /**
     * Returns the number of items in the collection.
     */
    final public function count() : int
    {
        return count($this->items);
    }
    
    
    
    //==================================================================================================================
    // JsonSerializable interface implementation
    //==================================================================================================================
    
    /**
     * @return T[]
     */
    public function jsonSerialize() : array
    {
        return $this->items;
    }
    
    
    
    //==================================================================================================================
    // Element operations (return a single, specific item from the collection)
    //==================================================================================================================
    
    /**
     * Returns the first item of the collection (that satisfies a condition, if a predicate function is specified).
     * @param ?callable(T $item=):bool $predicate A function to test each item for a condition.
     * @return T The first item in the collection.
     * @throws EmptyCollectionException Thrown if the collection is empty.
     * @throws NoPredicateResultException Thrown if no item satisfies the condition in predicate.
     */
    public function first(?callable $predicate = null)
    {
        if (empty($this->items)) {
            throw new EmptyCollectionException();
        }
        
        $items = ($predicate === null) ? $this->items : array_values(array_filter($this->items, $predicate));
        if (empty($items)) {
            throw new NoPredicateResultException();
        }
        
        return $items[0];
    }
    
    /**
     * Returns the first item of the collection (that satisfies a condition, if a predicate function is specified) or a default value if no such item is found.
     * @param ?T $default The default value to return if no item is found.
     * @param ?callable(T $item=):bool $predicate A function to test each item for a condition.
     * @return ?T The first item of the collection or the specified default value.
     */
    public function firstOrDefault($default, ?callable $predicate = null)
    {
        if ($predicate === null) {
            return $this->items[0] ?? $default;
        }
        
        return array_values(array_filter($this->items, $predicate))[0] ?? $default;
    }
    
    
    /**
     * Returns the last item of the collection (that satisfies a condition, if a predicate function is specified).
     * @param ?callable(T $item=):bool $predicate A function to test each item for a condition.
     * @return T The last item of the collection.
     * @throws EmptyCollectionException Thrown if the collection is empty.
     * @throws NoPredicateResultException Thrown if no item satisfies the condition in predicate.
     */
    public function last(?callable $predicate = null)
    {
        if (empty($this->items)) {
            throw new EmptyCollectionException();
        }
        
        $items = ($predicate === null) ? $this->items : array_values(array_filter($this->items, $predicate));
        if (empty($items)) {
            throw new NoPredicateResultException();
        }
        
        return end($items);
    }
    
    /**
     * Returns the last item of the collection (that satisfies a condition, if a predicate function is specified) or the specified default value if no such item is found.
     * @param ?T $default The default value to return if no item is found.
     * @param ?callable(T $item=):bool $predicate A function to test each item for a condition.
     * @return ?T The last item of the collection or the specified default value.
     */
    public function lastOrDefault($default, ?callable $predicate = null)
    {
        $items = ($predicate === null) ? $this->items : array_values(array_filter($this->items, $predicate));
        
        return empty($items) ? $default : end($items);
    }
    
    
    /**
     * Returns the only item of the collection (that satisfies a specified condition, if a predicate function is specified) or throws an exception if more than one item exists.
     * @param ?callable(T $item=):bool $predicate A function to test each item for a condition.
     * @return T The single item of the collection.
     * @throws EmptyCollectionException: Thrown if the collection is empty.
     * @throws TooManyItemsException: Thrown if the collection contains more than one item.
     * @throws NoPredicateResultException Thrown if no item satisfies the condition in predicate.
     * @throws TooManyPredicateResultsException: Thrown if more than one item satisfies the condition in predicate.
     */
    public function single(?callable $predicate = null)
    {
        if (empty($this->items)) {
            throw new EmptyCollectionException();
        }
        
        if ($predicate === null) {
            if (count($this->items) > 1) {
                throw new TooManyItemsException();
            }
            
            return $this->items[0];
        }
        
        $items = array_values(array_filter($this->items, $predicate));
        if (empty($items)) {
            throw new NoPredicateResultException();
        }
        if (count($items) > 1) {
            throw new TooManyPredicateResultsException();
        }
        
        return $items[0];
    }
    
    
    /**
     * Returns the only item of the collection (that satisfies a specified condition, if a predicate function is specified) or throws an exception if more than one item exists.
     * @param ?T $default The default value to return if no item is found.
     * @param ?callable(T $item=):bool $predicate A function to test each item for a condition.
     * @return ?T The single item of the collection or the specified default value.
     * @throws TooManyItemsException: Thrown if the collection contains more than one item.
     * @throws TooManyPredicateResultsException: Thrown if more than one item satisfies the condition in predicate.
     */
    public function singleOrDefault($default, ?callable $predicate = null)
    {
        if (empty($this->items)) {
            return $default;
        }
        
        if ($predicate === null) {
            if (count($this->items) > 1) {
                throw new TooManyItemsException();
            }
            
            return $this->items[0];
        }
        
        $items = array_values(array_filter($this->items, $predicate));
        if (empty($items)) {
            return $default;
        }
        if (count($items) > 1) {
            throw new TooManyPredicateResultsException();
        }
        
        return $items[0];
    }
    
    
    /**
     * Returns the minimum value of the collection (transformed by the specified selector function, if specified).
     * @param ?callable(mixed $item=):mixed $selector A transform function invoked on each item of the collection before computing the minimum resulting value.
     * @return mixed The minimum value in the collection.
     * @throws EmptyCollectionException: Thrown if the collection is empty.
     */
    public function min(?callable $selector = null)
    {
        if (empty($this->items)) {
            throw new EmptyCollectionException();
        }
        
        if ($selector === null) {
            return min($this->items);
        }
        
        return min(array_map($selector, $this->items));
    }
    
    /**
     * Returns the maximum value of the collection (transformed by the specified selector function, if specified).
     * @param ?callable(mixed $item=):mixed $selector A transform function invoked on each item of the collection before computing the maximum resulting value.
     * @return mixed The maximum value in the collection.
     * @throws EmptyCollectionException: Thrown if the collection is empty.
     */
    public function max(?callable $selector = null)
    {
        if (empty($this->items)) {
            throw new EmptyCollectionException();
        }
        
        if ($selector === null) {
            return max($this->items);
        }
        
        return max(array_map($selector, $this->items));
    }
    
    
    
    //==================================================================================================================
    // Mutation methods
    //   Use these functions to add, remove, filter or reorder collection's items.
    //   Depending on the implementation  of "getModifiableInstance" method, they return the current collection instance
    //   or a new one.
    //==================================================================================================================
    
    /**
     * Adds a value to the end of the collection.
     * @param mixed $item The value to append to the collection.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function append($item): self
    {
        if ($this->validator !== null) {
            ($this->validator)($item, null);
        }
        
        $collection =  $this->getModifiableInstance();
        $collection->items[] = $item;
        
        return $collection;
    }
    
    /**
     * Adds a value to the beginning of the collection.
     * @param mixed $item The value to prepend to the collection.
     * @return Collection<T> The current collection
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function prepend($item): self
    {
        if ($this->validator !== null) {
            ($this->validator)($item, null);
        }
    
        $collection = $this->getModifiableInstance();
        array_unshift($collection->items, $item);
        return $collection;
    }
    
    
    /**
     * Randomizes the order of the items in the collection.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function shuffle(): self
    {
        $collection = $this->getModifiableInstance();
        shuffle($collection->items);
        
        return $collection;
    }
    
    
    /**
     * Inverts the order of the items in the collection.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function reverse(): self
    {
        $collection = $this->getModifiableInstance();
        $collection->items = array_reverse($this->items);
        
        return $collection;
    }
    
    
    
    
    
    //==================================================================================================================
    // Partitioning methods
    // Divides an input collection into two sections, without rearranging the items, and then returning one of the sections.
    //==================================================================================================================
    
    /**
     * Bypasses a specified number of items in the collection and then returns the remaining items (equivalent of "array_slice" PHP function with $offset = $count).
     * @param int $count The number of items to skip before returning the remaining items.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function skip(int $count) : self
    {
        $collection = $this->getModifiableInstance();
        $collection->items = array_slice($this->items, $count);
        
        return $collection;
    }
    
    
    /**
     * Returns a new enumerable collection that contains the items from source with the last count items of the source collection omitted.
     * Returns a new enumerable collection that contains the last count items from source (equivalent of "array_slice" PHP function with $offset = items count - $count).
     * @param int $count The number of items to omit from the end of the collection.
     * @return static The current collection instance or a new instance if the collection is immutable.
     * TODO @ return static The current collection instance (or a new instance if the collection is immutable) that contains the items from source minus count items from the end of the collection.
     */
    public function skipLast(int $count) : self
    {
        $collection = $this->getModifiableInstance();
        $length = max(0, count($this->items) - $count);
        $collection->items = array_slice($this->items, 0, $length);
        
        return $collection;
    }
    
    /**
     * Bypasses items in the collection as long as a specified condition is true and then returns the remaining items.
     * @param callable(T $item, int $index=):bool $predicate A function to test each item for a condition.
     * @return static The current collection instance or a new instance if the collection is immutable.
     * TODO @ return static The current collection instance (or a new instance if the collection is immutable) that contains the items from the input collection starting at the first item in the linear series that does not pass the test specified by predicate.
     */
    public function skipWhile(callable $predicate) : self
    {
        $index = 0;
        foreach ($this->items as $key => $item) {
            if (! $predicate($item, $key)) {
                break;
            }
            $index++;
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = array_slice($this->items, $index);
        
        return $collection;
    }
    
    /**
     * Returns a specified number of contiguous items from the start of a collection (equivalent of "array_slice" PHP function with $offset = 0, $length = $count).
     * @param int $count The number of items to return.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function take(int $count) : self
    {
        $collection = $this->getModifiableInstance();
        $collection->items = array_slice($this->items, 0, $count);
        
        return $collection;
    }
    
    /**
     * Returns a new enumerable collection that contains the last count items from source (equivalent of "array_slice" PHP function with $offset = items count - $count).
     * @param int $count The number of items to return.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function takeLast(int $count) : self
    {
        $collection = $this->getModifiableInstance();
        $collection->items = array_slice($this->items, count($this->items) - $count);
        
        return $collection;
    }
    
    /**
     * Returns items from the collection as long as a specified condition is true.
     * @param callable(T $item, int $index=):bool $predicate A function to test each item for a condition.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function takeWhile(callable $predicate) : self
    {
        $count = 0;
        foreach ($this->items as $key => $item) {
            if (! $predicate($item, $key)) {
                break;
            }
            $count++;
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = array_slice($this->items, 0, $count);
        
        return $collection;
    }
    
    
    
    
    
    
    //==================================================================================================================
    // Aggregation methods
    // Computes a single value from a collection of values.
    //==================================================================================================================
    
    /**
     * Computes the average of the collection values.
     * @param ?callable(mixed $item):float $selector A transform function invoked on each item of the collection before computing the average resulting value.
     * @return float The average value of the collection.
     */
    public function average(?callable $selector = null): float
    {
        $items =  ($selector === null) ? $this->items : array_map($selector, $this->items);
        
        $count = (float)count($items);
        return $count ? array_sum($items)/$count : 0.;
    }
    
    
    
    //==================================================================================================================
    // IteratorAggregate interface implementation
    //==================================================================================================================
    
    /**
     * @return ArrayIterator<int, T>
     */
    final public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
    
    
    
    //==================================================================================================================
    // ArrayAccess interface implementation
    //==================================================================================================================
    
    final public function offsetExists($offset) : bool
    {
        return isset($this->items[$offset]);
    }
    
    #[\ReturnTypeWillChange]
    final public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }
    
    final public function offsetSet($offset, $value) : void
    {
        throw new BadMethodCallException("Direct modification of collection's items is not allowed, use appropriate methods instead.");
    }
    
    final public function offsetUnset($offset) : void
    {
        throw new BadMethodCallException("Direct removal of collection's items is not allowed, use appropriate methods instead.");
    }
}
