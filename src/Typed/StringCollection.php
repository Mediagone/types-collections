<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_string;
use function range;


/**
 * A strongly typed collection that can contain string values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<string>
 */
class StringCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only string instances.
            if (! is_string($item)) {
                throw new InvalidCollectionItemException($item, 'string', $index);
            }
        };
    }
    
    
    //==================================================================================================================
    // Constructors
    //==================================================================================================================
    
    /**
     * Generates a collection of string within the specified range.
     * @return static
     */
    public static function fromRange(string $start, string $end, int $step = 1)
    {
        return self::fromArray(range($start, $end, $step));
    }
}
