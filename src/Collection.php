<?php declare(strict_types=1);

namespace Mediagone\Types\Collections;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;


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
