<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_callable;


/**
 * A strongly typed collection that can contain callable values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<callable>
 */
class CallableCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only callable instances.
            if (! is_callable($item)) {
                throw new InvalidCollectionItemException($item, 'callable', $index);
            }
        };
    }
    
    
    //==================================================================================================================
    // Constructors
    //==================================================================================================================
    
    /**
     * Generates a new empty callable collection.
     */
    public static function new() : CallableCollection
    {
        return static::fromArray([]);
    }
    
    /**
     * Generates a new collection containing the specified callables.
     * @param callable[] $items
     * @return CallableCollection
     */
    public static function fromArray(array $items) : CallableCollection
    {
        return new static($items);
    }
}
