<?php

namespace Tests\Mediagone\Types\Collections\Fakes;


class FakeFoo
{
    private string $value;
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function __construct(string $value = '')
    {
        $this->value = $value;
    }
}
