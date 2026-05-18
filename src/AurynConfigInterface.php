<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface AurynConfigInterface
{
    /**
     * @return void
     */
    public function apply();

    /**
     * @param class-string|Extension ...$extensions
     * @return void
     */
    public function extend(...$extensions);

    /**
     * @return void
     */
    public function walk(string $key, callable $callback);
}
