<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface ModuleInterface
{
    /**
     * @return iterable<array-key, mixed>
     */
    public function __invoke(): iterable;
}
