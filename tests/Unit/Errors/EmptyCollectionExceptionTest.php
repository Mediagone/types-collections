<?php declare(strict_types=1);

namespace Tests\Mediagone\Types\Collections\Unit\Errors;

use Mediagone\Types\Collections\Errors\EmptyCollectionException;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Mediagone\Types\Collections\Errors\EmptyCollectionException
 */
final class EmptyCollectionExceptionTest extends TestCase
{
    //==================================================================================================================
    // Constructors tests
    //==================================================================================================================
    
    public function test_can_be_created() : void
    {
        $error = new EmptyCollectionException();
        
        self::assertSame('The collection is empty', $error->getMessage());
    }
    
    
}
