<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Errors;

use LogicException;


class TooManyItemsException extends LogicException
{
    public function __construct()
    {
        parent::__construct('There is more than one item in the collection.');
    }
}
