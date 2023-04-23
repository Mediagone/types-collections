<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Errors;

use LogicException;


class TooManyPredicateResultsException extends LogicException
{
    public function __construct()
    {
        parent::__construct('More than one item satisfies the condition in predicate.');
    }
}
