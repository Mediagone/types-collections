<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_int;
use function range;


/**
 * A strongly typed collection that can contain integer values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<int>
 */
class IntCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only integer instances.
            if (! is_int($item)) {
                throw new InvalidCollectionItemException($item, 'integer', $index);
            }
        };
    }
    
    
    //==================================================================================================================
    // Constructors
    //==================================================================================================================
    
    /**
     * Generates a collection of integers within the specified range.
     * @return static
     */
    public static function fromRange(int $start, int $end, int $step = 1)
    {
        return self::fromArray(range($start, $end, $step));
    }
}
