<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Errors;

use LogicException;


class EmptyCollectionException extends LogicException
{
    public function __construct()
    {
        parent::__construct('The collection is empty');
    }
}
