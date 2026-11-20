<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface AurynConfigInterface
{
    public function apply(): void;

    /**
     * @param class-string|Extension ...$extensions
     */
    public function extend(string|Extension ...$extensions): void;

    public function walk(string $key, callable $callback): void;
}
