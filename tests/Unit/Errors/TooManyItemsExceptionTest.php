<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Errors;

use Mediagone\Types\Collections\Errors\TooManyItemsException;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Errors\TooManyItemsException
 */
final class TooManyItemsExceptionTest extends TestCase
{
    //==================================================================================================================
    // Constructors tests
    //==================================================================================================================
    
    public function test_can_be_created() : void
    {
        $error = new TooManyItemsException();
        
        self::assertSame('There is more than one item in the collection.', $error->getMessage());
    }
    
    
}
