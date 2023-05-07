<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Typed;

use Closure;
use Mediagone\Types\Collections\Collection;
use Mediagone\Types\Collections\Errors\InvalidCollectionItemException;
use function is_float;


/**
 * A strongly typed collection that can contain float values, accessible by index and having methods for sorting, searching, and modifying the collection.
 * @extends Collection<float>
 */
class FloatCollection extends Collection
{
    //==================================================================================================================
    // 
    //==================================================================================================================
    
    protected function getValidator(): ?Closure
    {
        return static function($item, $index) {
            // Ensure that supplied items array contains only float instances.
            if (! is_float($item)) {
                throw new InvalidCollectionItemException($item, 'float', $index);
            }
        };
    }
}
