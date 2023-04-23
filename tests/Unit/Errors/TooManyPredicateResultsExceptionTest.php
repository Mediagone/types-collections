<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Errors;

use Mediagone\Types\Collections\Errors\TooManyPredicateResultsException;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Errors\TooManyPredicateResultsException
 */
final class TooManyPredicateResultsExceptionTest extends TestCase
{
    //==================================================================================================================
    // Constructors tests
    //==================================================================================================================
    
    public function test_can_be_created() : void
    {
        $error = new TooManyPredicateResultsException();
        
        self::assertSame('More than one item satisfies the condition in predicate.', $error->getMessage());
    }
    
    
}
