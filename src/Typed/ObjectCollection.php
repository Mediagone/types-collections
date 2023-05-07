<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_object;


/**
 * A strongly typed collection that can contain object values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<object>
 */
class ObjectCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only object instances.
            if (! is_object($item)) {
                throw new InvalidCollectionItemException($item, 'object', $index);
            }
        };
    }
}
