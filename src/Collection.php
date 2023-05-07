<?php declare(strict_types=1);

namespace Mediagone\Types\Collections;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Closure;
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
use TypeError;
use function array_chunk;
use function array_diff;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_search;
use function array_slice;
use function array_splice;
use function array_sum;
use function array_unshift;
use function array_values;
use function class_exists;
use function count;
use function end;
use function get_class;
use function in_array;
use function is_a;
use function max;
use function min;
use function shuffle;
use function sort;
use function usort;


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
    
    
    /**
     * Converts the collection into a new collection type, all items must be valid in the target collection.
     * @template U of Collection
     * @param class-string<U> $targetCollectionFqcn The target collection class-name to convert the current collection into.
     * @return U A new collection of the specified type containing the current collection's items.
     * @throws InvalidArgumentException Thrown if the specified collection class does not exist.
     * @throws TypeError Thrown if the specified collection class does not extend the Collection base class.
     * @throws TypeError Thrown if any item does not validate the target collection's validator constraints.
     */
    public function toCollection(string $targetCollectionFqcn) : Collection
    {
        if (! class_exists($targetCollectionFqcn)) {
            throw new InvalidArgumentException('Unknown collection class "' . $targetCollectionFqcn.'".');
        }
        
        if (! is_a($targetCollectionFqcn, __CLASS__, true)) {
            throw new TypeError('The collection can only be cast to another Collection class.');
        }
        
        return $targetCollectionFqcn::fromArray($this->items);
    }
    
    
    
    
    
    //==================================================================================================================
    // Element operations
    //==================================================================================================================
    
    /**
     * Determines whether the collection contains a specified item.
     * @param mixed $needle The value to locate in the collection.
     * @param ?callable(mixed $item, mixed $needle):bool $comparer An equality comparer to compare values, or 'null' to use the default equality comparer.
     * @return bool Returns 'true' if the source collection contains an item that has the specified value; otherwise, 'false'.
     */
    public function contains($needle, ?callable $comparer = null) : bool
    {
        if ($comparer !== null) {
            foreach ($this->items as $item) {
                if ($comparer($item, $needle)) {
                    return true;
                }
            }
            
            return false;
        }
        
        return in_array($needle, $this->items, true);
    }
    
    // TODO indexOf / find($item)?
    
    
    /**
     * Returns the first item of the collection (that satisfies a condition, if a predicate function is specified).
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
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
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
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
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
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
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
     * @return ?T The last item of the collection or the specified default value.
     */
    public function lastOrDefault($default, ?callable $predicate = null)
    {
        if ($predicate === null) {
            $items = $this->items;
        }
        else {
            $items = array_values(array_filter($this->items, $predicate));
        }
        
        return empty($items) ? $default : end($items);
    }
    
    
    /**
     * Returns the only item of the collection (that satisfies a specified condition, if a predicate function is specified) or throws an exception if more than one item exists.
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
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
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
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
    
    
    
    //==================================================================================================================
    // Mutation methods
    //   Use these functions to add, remove, filter or reorder collection's items.
    //   Depending on the implementation  of "getModifiableInstance" method, they return the current collection instance
    //   or a new one.
    //==================================================================================================================
    
    final public function remove($item): self
    {
        $index = array_search($item, $this->items, true);
        if ($index === false) {
            throw new LogicException('Item not in the collection');
        }
        
        $collection = $this->getModifiableInstance();
        array_splice($this->items, (int)$index, 1);
        
        return $collection;
    }
    
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
     * Concatenates a collection at the end of the current collection.
     * @param Collection<T> $other The collection to concatenate to the current collection, must be an instance or a subclass of the current collection (covariant classes only).
     * @return static The current collection instance or a new instance if the collection is immutable
     * @throws TypeError Thrown if both collections to concatenate are not of the same type.
     */
    final public function concat($other): self
    {
        // Allow to use a more derived type (covariant) than current collection.
        if (! is_a($other, get_class($this))) {
            throw new TypeError('Invalid collection to concatenate ('.get_class($other).'), you can only concatenate covariant collections.');
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = array_merge($this->items, $other->items);
        
        return $collection;
    }
    
    
    
    
    /**
     * Computes the set difference with the specified collection.
     * @note Equivalent to the "array_diff" PHP function.
     * @param Collection<T> $other A collection whose items that also occur in the first collection will be removed from the returned sequence.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function except(Collection $other)
    {
        $items = [];
        foreach ($this->items as $item) {
            if (! in_array($item, $other->items, true)) {
                $items[] = $item;
            }
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = $items;
        
        return $collection;
    }
    
    /**
     * Computes the set difference with the specified collection according to a specified key selector function.
     * @param Collection<T> $other A collection whose items that also occur in the first collection will be removed from the returned sequence.
     * @param callable(T $item):mixed $keySelector A function to extract the key for each item.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function exceptBy(Collection $other, callable $keySelector)
    {
        $items = [];
        $otherKeys = array_map($keySelector, $other->items);
        foreach ($this->items as $item) {
            $key = $keySelector($item);
            if (! in_array($key, $otherKeys, true)) {
                $items[] = $item;
            }
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = $items;
        
        return $collection;
    }
    
    
    /**
     * Computes the set intersection of two collections.
     * @param Collection<T> $other A collection whose distinct items that also appear in the current collection will be returned.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function intersect(Collection $other)
    {
        $items = [];
        foreach ($this->items as $item) {
            if (in_array($item, $other->items, true)) {
                $items[] = $item;
            }
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = $items;
        
        return $collection;
    }
    
    
    /**
     * Computes the set difference of two sequences according to a specified key selector function.
     * @param Collection<T> $other A collection whose items that also occur in the first collection will be removed from the returned sequence.
     * @param callable(T $item):mixed $keySelector A function to extract the key for each item.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function intersectBy(Collection $other, callable $keySelector)
    {
        $items = [];
        $otherKeys = array_map($keySelector, $other->items);
        foreach ($this->items as $item) {
            $key = $keySelector($item);
            if (in_array($key, $otherKeys, true)) {
                $items[] = $item;
            }
        }
        
        $collection = $this->getModifiableInstance();
        $collection->items = $items;
        
        return $collection;
    }
    
    
    /**
     * Filters the collection items based on a predicate.
     * @param callable(T $item):bool $predicate A function to test each item for a condition.
     * @return static The current collection instance or a new instance if the collection is immutable.
     */
    public function where(callable $predicate)
    {
        $collection = $this->getModifiableInstance();
        $collection->items = array_values(array_filter($this->items, $predicate));
        
        return $collection;
    }
    
    
    /**
     * Returns distinct items from the collection.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    public function distinct() : self
    {
        $collection = $this->getModifiableInstance();
        
        $items = [];
        foreach ($this->items as $item) {
            if (! in_array($item, $items, true)) {
                $items[] = $item;
            }
        }
        
        $collection->items = $items;
        
        return $collection;
    }
    
    /**
     * Returns distinct items from the collection according to a specified key selector function.
     * @param callable(T $item):mixed $keySelector A function to extract the key for each item.
     * @return static<T>
     */
    public function distinctBy(callable $keySelector) : self
    {
        $collection = $this->getModifiableInstance();
        
        $items = [];
        $itemsKey = [];
        foreach ($this->items as $item) {
            $key = $keySelector($item);
            if (! in_array($key, $itemsKey, true)) {
                $itemsKey[] = $key;
                $items[] = $item;
            }
        }
        
        $collection->items = $items;
        
        return $collection;
    }
    
    
    /**
     * Bypasses a specified number of items in the collection and then returns the remaining items.
     * @note Equivalent to the "array_slice" PHP function: array_slice($array, offset: $count)
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
     * Bypasses a specified number of items at the end of the collection and then returns the remaining items.
     * @note Equivalent to the "array_slice" PHP function: array_slice($array, offset: count($array) - $count)
     *   TODO Returns a new collection that contains the items from source with the last count items of the source collection omitted.
     *   Returns a new collection that contains the items from the current collection except the last count omitted.
     * @param int $count The number of items to omit from the end of the collection.
     * @return static The current collection instance or a new instance if the collection is immutable.
     * TODO @ return static The current collection instance (or a new instance if the collection is immutable) that contains the items from source minus count items from the end of the collection.
     */
    public function skipLast(int $count) : self
    {
        $length = max(0, count($this->items) - $count);
        
        $collection = $this->getModifiableInstance();
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
     * Returns a specified number of contiguous items from the start of the collection.
     * @note Equivalent to the "array_slice" PHP function: array_slice($array, offset: 0, length: $count)
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
     * Returns a new enumerable collection that contains the last count items from source.
     * @note Equivalent to the "array_slice" PHP function: array_slice($array, offset: count($array) - $count)
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
    
    
    
    /**
     * Splits the items of the collection into chunks of specified size.
     * @param positive-int $size The maximum size of each chunk.
     * @return static[] An array of new collections that contain the split items.
     */
    public function chunk(int $size) : array
    {
        $chunks = array_chunk($this->items, $size);
        
        $collections = [];
        foreach ($chunks as $chunk) {
            $collections[] = static::fromArray($chunk);
        }
        
        return $collections;
    }
    
    
    /**
     * Groups the items of the collection according to a specified key selector function.
     * @param callable(T $item):string $keySelector A function to extract the key for each item.
     * @return static[] An array of new collections that contain the grouped items.
     */
    public function groupBy(callable $keySelector)
    {
        $groups = [];
        
        foreach ($this->items as $item) {
            $key = (string)$keySelector($item);
            if (! isset($groups[$key])) {
                $groups[$key] = static::fromArray([]);
            }
            
            $groups[$key]->append($item);
        }
        
        return $groups;
    }
    
    
    
    //==================================================================================================================
    // Ordering methods
    //==================================================================================================================
    
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
    
    
    /**
     * Sorts the items of the collection in ascending order according to a key.
     * @note To compare objects or class instances, it is recommended to use the "sortBy" method instead since the result of comparing incomparable values is undefined and should not be relied upon.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function sort(): self
    {
        $collection = $this->getModifiableInstance();
        sort($this->items);
        //usort($this->items, static fn($a, $b) => $a <=> $b);
        
        return $collection;
    }
    
    
    /**
     * Sorts the items of the collection in descending order according to a key.
     * @note To compare objects or class instances, it is recommended to use the "sortBy" method instead since the result of comparing incomparable values is undefined and should not be relied upon.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function sortDescending(): self
    {
        $collection = $this->getModifiableInstance();
        usort($this->items, static fn($a, $b) => $b <=> $a);
        
        return $collection;
    }
    
    
    /**
     * Sorts the items of the collection in ascending order according to a key.
     * @param callable(T $item):mixed $keySelector A function to extract the key for each item.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function sortBy(callable $keySelector): self
    {
        $collection = $this->getModifiableInstance();
        usort($this->items, static fn($a, $b) => $keySelector($a) <=> $keySelector($b));
        
        return $collection;
    }
    
    /**
     * Sorts the items of the collection in descending order according to a key.
     * @param callable(T $item):mixed $keySelector A function to extract the key for each item.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function sortByDescending(callable $keySelector): self
    {
        $collection = $this->getModifiableInstance();
        usort($this->items, static fn($a, $b) => -($keySelector($a) <=> $keySelector($b)));
        
        return $collection;
    }
    
    
    
    //==================================================================================================================
    // Aggregation methods
    // Computes a single value from a collection of values.
    //==================================================================================================================
    
    /**
     * Returns the minimum value of the collection (transformed by the specified selector function, if specified).
     * @param ?callable(mixed $item):mixed $selector A transform function invoked on each item of the collection before computing the minimum resulting value.
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
     * @param ?callable(mixed $item):mixed $selector A transform function invoked on each item of the collection before computing the maximum resulting value.
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
    
    /**
     * Computes the average of the collection values.
     * @param ?callable(mixed $item):float $selector A transform function invoked on each item of the collection before computing the average resulting value.
     * @return float The average value of the collection.
     * @throws InvalidCollectionOperationException Thrown if the collection contains no items.
     */
    public function average(?callable $selector = null): float
    {
        $items =  ($selector === null) ? $this->items : array_map($selector, $this->items);
        
        $count = (float)count($items);
        if ($count === 0.) {
            throw InvalidCollectionOperationException::emptyCollection();
        }
        
        return array_sum($items) / $count;
    }
    
    /**
     * Computes the sum of a collection of numeric values.
     * @param ?callable(mixed $item):float $selector A transform function to apply to each item of the input collection.
     * @return float The sum of the values in the collection.
     */
    public function sum(?callable $selector = null): float
    {
        if ($selector === null) {
            return (float)array_sum($this->items);
        }
        
        return (float)array_sum(array_map($selector, $this->items));
    }
    
    /**
     * Applies an accumulator function over a sequence.
     * @note Equivalent to the "array_reduce" PHP function.
     * @param mixed $initial The initial accumulator value.
     * @param callable(mixed $total, T $item):mixed $accumulator An accumulator function to be invoked on each item.
     * @return mixed The final accumulator value.
     */
    public function aggregate($initial, callable $accumulator)
    {
        foreach ($this->items as $item) {
            $initial = $accumulator($initial, $item);
        }
        
        return $initial;
    }
    
    
    
    //==================================================================================================================
    // Projection methods
    //==================================================================================================================
    
    /**
     * Projects each item of the collection into a new form and return an array that contains the transformed items of the collection.
     * @note Equivalent to "array_map" PHP function.
     * @param callable(T $item):mixed $selector A transform function to apply to each item of the collection.
     * @return MixedCollection A new collection that contains the transformed items of the current collection.
     */
    public function select(callable $selector) : MixedCollection
    {
        return MixedCollection::fromArray(array_map($selector, $this->items));
    }
    
    /**
     * Correlates the items of two collection based on matching keys.
     * @template U The type of the items in the other collection.
     * @param Collection<U> $other The collection to join to the current collection.
     * @param callable(T $item): mixed $keySelector A function to extract the join key from each item of the current collection.
     * @param callable(U $item): mixed $otherKeySelector A function to extract the join key from each item of the other collection.
     * @param callable(T $item, U $otherItem): mixed $resultSelector A function to create a result element from two matching elements.
     * @param ?callable(mixed $key, mixed $otherKey): bool $comparer An equality comparer function to compare keys, or null to use the default equality comparer to compare keys.
     * @return MixedCollection A MixedCollection that contains items obtained by performing an inner join on two collections.
     */
    public function join(Collection $other, callable $keySelector, callable $otherKeySelector, callable $resultSelector, ?callable $comparer = null) : MixedCollection
    {
        // If no comparer function is supplied, use the default equality comparer.
        if ($comparer === null) {
            $comparer = static fn($a, $b) => $a === $b;
        }
        
        $results = [];
        foreach ($this->items as $item) {
            $key = $keySelector($item);
            foreach ($other->items as $otherItem) {
                $otherKey = $otherKeySelector($otherItem);
                if ($comparer($key, $otherKey)) {
                    $results[] = $resultSelector($item, $otherItem);
                }
            }
        }
        
        return MixedCollection::fromArray($results);
    }
    
    
    
    //==================================================================================================================
    // Traversal methods
    //==================================================================================================================
    
    /**
     * Applies a callback function to each item of the collection.
     * @param callable(T $item): void $func A callback function to apply to each item of the input collection.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    public function forEach(callable $func): self
    {
        foreach ($this->items as $item) {
            $func($item);
        }
        
        return $this;
    }
    
    
    
    
    
    
    
    
    
    //==================================================================================================================
    // Quantifier methods
    // Returns a Boolean value that indicates whether some or all of the items in a collection satisfy a condition.
    //==================================================================================================================
    
    /**
     * Determines whether all items of the collection satisfy a condition.
     * @param callable(mixed $item):bool $predicate A function to test each item for a condition.
     * @return bool Returns 'true' if every item of the collection passes the test in the specified predicate, or if the sequence is empty; otherwise, false.
     */
    public function all(callable $predicate) : bool
    {
        foreach ($this->items as $item) {
            if ($predicate($item) === false) {
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Determines whether a collection contains any items; if a predicate function is specified, determines whether any item of the collection satisfies a condition.
     * @note Equivalent to the "is_empty" PHP function, if called without a predicate function.
     * @param ?callable(mixed $item):bool $predicate A function to test each item for a condition.
     * @return bool Returns 'true' if the collection contains any items; otherwise, false.
     */
    public function any(?callable $predicate = null) : bool
    {
        if ($predicate !== null) {
            foreach ($this->items as $item) {
                if ($predicate($item) === true) {
                    return true;
                }
            }
            
            return false;
        }
        
        return !empty($this->items);
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
