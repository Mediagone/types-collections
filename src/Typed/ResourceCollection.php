<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_resource;


/**
 * A strongly typed collection that can contain resource values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<resource>
 */
class ResourceCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only resource instances.
            if (! is_resource($item)) {
                throw new InvalidCollectionItemException($item, 'resource', $index);
            }
        };
    }
}
