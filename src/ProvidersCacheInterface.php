<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface ProvidersCacheInterface
{
    /**
     * @return array<array-key, mixed>|null The cached configuration, null on a cache miss.
     */
    public function read(): ?array;

    /**
     * @param array<array-key, mixed> $config
     */
    public function write(array $config): void;
}
