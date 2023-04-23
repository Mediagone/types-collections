<?php declare(strict_types=1);

namespace Mediagone\Types\Collections\Errors;

use LogicException;


class NoPredicateResultException extends LogicException
{
    public function __construct()
    {
        parent::__construct('No item satisfies the condition in predicate.');
    }
}
