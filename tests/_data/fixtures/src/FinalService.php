<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests;

/**
 * Final and stateful: lazy objects initialize on the first property access.
 */
final class FinalService
{
    public function __construct(private string $value = 'FinalService')
    {
    }

    public function value(): string
    {
        return $this->value;
    }
}
