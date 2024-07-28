<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

/**
 * @psalm-api
 */
interface AurynConfigInterface
{
    /**
     * @return void
     */
    public function resolve();

    /**
     * @param class-string|Extension ...$extensions
     * @return void
     */
    public function extend(...$extensions);

    /**
     * @param string $key
     * @param callable $callback
     * @return void
     */
    public function walk(string $key, callable $callback);
}
