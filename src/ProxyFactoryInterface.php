<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface ProxyFactoryInterface
{
    /**
     * @param class-string<object> $className
     */
    public function __invoke(string $className, callable $callback): object;
}
