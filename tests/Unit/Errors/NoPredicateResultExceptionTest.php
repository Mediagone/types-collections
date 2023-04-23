<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Errors;

use Mediagone\Types\Collections\Errors\NoPredicateResultException;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Errors\NoPredicateResultException
 */
final class NoPredicateResultExceptionTest extends TestCase
{
    //==================================================================================================================
    // Constructors tests
    //==================================================================================================================
    
    public function test_can_be_created() : void
    {
        $error = new NoPredicateResultException();
        
        self::assertSame('No item satisfies the condition in predicate.', $error->getMessage());
    }
    
    
}
