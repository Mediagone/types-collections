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
use OutOfBoundsException;
use TypeError;
use ValueError;
use function array_chunk;
use function array_fill;
use function array_filter;
use function array_map;
use function array_merge;
use function array_rand;
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
use function is_iterable;
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
     * @return static A new empty collection.
     */
    public static function new(): Collection
    {
        return static::fromArray([]);
    }
    
    
    /**
     * Generates a new collection containing the specified items.
     * @param T[] $items
     * @return static A new collection that contains the specified items.
     */
    public static function fromArray(array $items): Collection
    {
        return new static($items);
    }
    
    
    /**
     * Generates a collection containing one repeated value.
     * @param T $value The value to be repeated.
     * @param int $count The number of times to repeat the value in the generated collection.
     * @return static A new collection that contains a repeated value.
     */
    public static function fromRepeatedValue($value, int $count)
    {
        return self::fromArray( array_fill(0, $count, $value));
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
     * @template TOther of Collection
     * @param class-string<TOther> $targetCollectionFqcn The target collection class-name to convert the current collection into.
     * @return TOther A new collection of the specified type containing the current collection's items.
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
     * @param ?callable(T $item, mixed $needle):bool $comparer An equality comparer to compare values, or 'null' to use the default equality comparer.
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
     * Returns the first item of the collection (that satisfies the optional condition) or null if no such item is found.
     * @param (callable(T $item):bool)|null $predicate A function to test each item for a condition.
     * @return ?T The first item of the collection or null.
     */
    public function firstOrNull(?callable $predicate = null)
    {
        if ($predicate === null) {
            return $this->items[0] ?? null;
        }
        
        return array_values(array_filter($this->items, $predicate))[0] ?? null;
    }
    
    /**
     * Returns the first item of the collection (that satisfies the optional condition) or a default value if no such item is found.
     * @param T $default The default value to return if no item is found.
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
     * @return T The first item of the collection or the specified default value.
     */
    public function firstOrDefault($default, ?callable $predicate = null)
    {
        if ($default === null) {
            throw new InvalidArgumentException('Default value should not be null');
        }
        
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
    
    
    /**
     * Returns one or more random items of the collection.
     * @param int<1, max> $count The number of unique random items to get (must be less or equal to the collection's items count).
     * @return T[] The picked random items.
     * @throws ValueError Thrown if the $count argument is 
     * @throws EmptyCollectionException: Thrown if the collection is empty.
     */
    public function random(int $count = 1, bool $preserveCollectionOrder = false): array
    {
        if (empty($this->items)) {
            throw new EmptyCollectionException();
        }
        
        // Get random indexes
        $randomIndexes = array_rand($this->items, $count);
        if (! is_array($randomIndexes)) {
            $randomIndexes = [$randomIndexes];
        }
        
        if (! $preserveCollectionOrder) {
            // Shuffle the indexes, since they are always returned in the order they were present in the original array.
            shuffle($randomIndexes);
        }
        
        $results = [];
        foreach ($randomIndexes as $index) {
            $results[] = $this->items[$index];
        }
        
        return $results;
    }
    
    
    
    //==================================================================================================================
    // Transform methods
    // Depending on the implementation  of "getModifiableInstance" method, they return the current collection instance or a new one.
    //==================================================================================================================
    
    /**
     * Adds a value to the end of the collection.
     * @param T ...$items The value(s) to append to the collection.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function append(...$items): self
    {
        $collection = $this->getModifiableInstance();
        foreach ($items as $item) {
            if ($this->validator !== null) {
                ($this->validator)($item, null);
            }
            
            $collection->items[] = $item;
        }
        
        return $collection;
    }
    
    /**
     * Adds a value to the beginning of the collection.
     * @param T ...$items The value(s) to prepend to the collection.
     * @return Collection<T> The current collection
     * @return static The current collection instance or a new instance if the collection is immutable
     */
    final public function prepend(...$items): self
    {
        $collection = $this->getModifiableInstance();
        foreach (array_reverse($items) as $item) {
            if ($this->validator !== null) {
                ($this->validator)($item, null);
            }
            
            array_unshift($collection->items, $item);
        }
        
        return $collection;
    }
    
    
    /**
     * Remove the specified value from the collection.
     * @param T $item The value to remove from the collection. 
     * @throws LogicException Thrown if the collection doesn't contain the specified value.
     * @return static The current collection instance or a new instance if the collection is immutable
     */
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
    public function except(Collection $other): self
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
    public function exceptBy(Collection $other, callable $keySelector): self
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
    public function intersect(Collection $other): self
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
    public function intersectBy(Collection $other, callable $keySelector): self
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
    public function where(callable $predicate): self
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
     * @param int $count The number of items to omit from the end of the collection.
     * @return static The current collection instance or a new instance if the collection is immutable.
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
    //==================================================================================================================

    /**
     * Returns the number of items in a collection.
     * @return int The number of items in the input collection.
     */
    public function count(): int
    {
        return count($this->items);
    }
    
    /**
     * Returns the minimum value of the collection (transformed by the specified selector function, if specified).
     * @template TValue The return type of the selector function.
     * @param ?callable(T $item):TValue $selector A transform function invoked on each item of the collection before computing the minimum resulting value.
     * @return T|TValue The minimum value in the collection.
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
     * @template TValue The return type of the selector function.
     * @param ?callable(T $item):TValue $selector A transform function invoked on each item of the collection before computing the maximum resulting value.
     * @return T|TValue The maximum value in the collection.
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
     * @param ?callable(T $item):float $selector A transform function invoked on each item of the collection before computing the average resulting value.
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
     * @param ?callable(T $item):float $selector A transform function to apply to each item of the input collection.
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
     * @template TValue The type of the accumulator result.
     * @param TValue $initial The initial accumulator value.
     * @param callable(TValue $total, T $item):TValue $accumulator An accumulator function to be invoked on each item.
     * @return TValue The final accumulator value.
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
     * Projects each item of the collection to a collection and flattens the resulting collections into one collection.
     * @template TCollection The type of the intermediate items collected by $iterableSelector.
     * @param callable(T $item): iterable<TCollection> $iterableSelector A transform function to apply to each item of the input collection that returns an iterable of intermediate items.
     * @template TResult The type of the items of the resulting collection.
     * @param ?callable(T $item, TCollection $subItem):TResult $resultSelector A transform function to apply to each item of the intermediate collection.
     * @return MixedCollection A new collection that contains items obtained by performing the one-to-many projection over the source collection.
     */
    public function selectMany(callable $iterableSelector, ?callable $resultSelector = null) : MixedCollection
    {
        if ($resultSelector === null) {
            $resultSelector = static fn($item, $subItem) => $subItem;
        }
        
        $items = [];
        foreach ($this->items as $item) {
            $iterable = $iterableSelector($item);
            if (! is_iterable($iterable)) {
                throw new TypeError("Selected collection object is not an iterable type (got '".gettype($iterable)."')");
            }
            
            foreach ($iterable as $subItem) {
                $items[] = $resultSelector($item, $subItem);
            }
        }
        
        return MixedCollection::fromArray($items);
    }
    
    /**
     * Groups the items of the collection according to a specified key selector function.
     * @param callable(T $item):string $keySelector A function to extract the key for each item.
     * @return static[] An array of new collections that contain the grouped items.
     */
    public function groupBy(callable $keySelector): array
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
    
    /**
     * Correlates the items of two collection based on matching keys.
     * @template TOther The type of the items in the other collection.
     * @template TResult The type of the joined result.
     * @template TKey The return type of the keySelector for the current collection.
     * @template TKeyOther The return type of the keySelector for the other collection.
     * @param Collection<TOther> $other The collection to join to the current collection.
     * @param callable(T $item): TKey $keySelector A function to extract the join key from each item of the current collection.
     * @param callable(TOther $item): TKeyOther $otherKeySelector A function to extract the join key from each item of the other collection.
     * @param callable(T $item, TOther $otherItem): TResult $resultSelector A function to create a result element from two matching items.
     * @param ?callable(TKey $key, TKeyOther $otherKey): bool $comparer An equality comparer function to compare keys, or null to use the default equality comparer to compare keys.
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
    // Quantifier methods
    // Returns a Boolean value that indicates whether some or all of the items in a collection satisfy a condition.
    //==================================================================================================================
    
    /**
     * Determines whether all items of the collection satisfy a condition.
     * @param callable(T $item):bool $predicate A function to test each item for a condition.
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
     * @param ?callable(T $item):bool $predicate A function to test each item for a condition.
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
    // Misc methods
    //==================================================================================================================
    
    // JsonSerializable interface implementation
    
    /**
     * @return T[]
     */
    public function jsonSerialize() : array
    {
        return $this->items;
    }
    
    
    // IteratorAggregate interface implementation
    
    /**
     * @return ArrayIterator<int, T>
     */
    final public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
    
    
    // ArrayAccess interface implementation
    
    final public function offsetExists($offset) : bool
    {
        return isset($this->items[$offset]);
    }
    
    /**
     * @throws OutOfBoundsException Thrown if the specified offset is greater than the number of items in the collection.
     */
    #[\ReturnTypeWillChange]
    final public function offsetGet($offset)
    {
        if (!isset($this->items[$offset])) {
            throw new OutOfBoundsException("The index ($offset) is not defined, there is only ".count($this->items)." items in the collection.");
        }
        
        return $this->items[$offset];
    }
    
    /**
     * @throws BadMethodCallException The "set" operation is not allowed on collections.
     */
    final public function offsetSet($offset, $value) : void
    {
        throw new BadMethodCallException("Direct modification of a collection's items is not allowed, use appropriate methods instead.");
    }
    
    /**
     * @throws BadMethodCallException The "unset" operation is not allowed on collections.
     */
    final public function offsetUnset($offset) : void
    {
        throw new BadMethodCallException("Direct removal of a collection's items is not allowed, use appropriate methods instead.");
    }
}
