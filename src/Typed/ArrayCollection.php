<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_array;


/**
 * A strongly typed collection that can contain array values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<mixed[]>
 */
class ArrayCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only array instances.
            if (! is_array($item)) {
                throw new InvalidCollectionItemException($item, 'array', $index);
            }
        };
    }
}
