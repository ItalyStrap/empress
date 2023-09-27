<?php

declare(strict_types=1);

namespace ItalyStrap\Tests;

class SomeConcrete implements SomeInterface
{
    public function render(): string
    {
        return 'SomeConcrete';
    }
}
