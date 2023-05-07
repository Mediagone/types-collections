<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use Mediagone\Types\Collections\Errors\UnknownClassException;
use function class_exists;
use function is_a;


/**
 * A strongly typed collection that can contain class instances, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @template C
 * @extends Collection<C>
 */
abstract class ClassCollection extends Collection
{
    //==================================================================================================================
    // Abstract methods
    //==================================================================================================================
    
    abstract protected static function classFqcn(): string;
    
    
    
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that the supplied items array contains only allowed class instances.
            $className = static::classFqcn();
            if (! is_a($item, $className)) {
                throw new InvalidCollectionItemException($item, $className, $index);
            }
        };
    }
    
    
    //==================================================================================================================
    // Constructors
    //==================================================================================================================
    
    /**
     * Generates a new empty class collection.
     * @return static
     */
    final public static function new(): self
    {
        return static::fromArray([]);
    }
    
    /**
     * Generates a new collection containing the specified classe instances.
     * @param array<C> $items
     * @throws UnknownClassException If the collection's class doesn't exist.
     * @throws InvalidCollectionItemException Thrown if the supplied array doesn't contain only class instances.
     * @return static
     */
    public static function fromArray(array $items) : ClassCollection
    {
        $className = static::classFqcn();
        
        // Check if the supplied class name exists.
        if (! class_exists($className)) {
            throw new UnknownClassException("Unknown class '$className'");
        }
        
        return new static($items);
    }
}
