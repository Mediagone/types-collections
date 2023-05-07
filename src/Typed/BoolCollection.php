<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_bool;


/**
 * A strongly typed collection that can contain boolean values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<bool>
 */
class BoolCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only boolean instances.
            if (! is_bool($item)) {
                throw new InvalidCollectionItemException($item, 'boolean', $index);
            }
        };
    }
}
