<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

/**
 * Default cache: never reads, never writes.
 * Install italystrap/empress-cache for a file based implementation.
 */
final class NullProvidersCache implements ProvidersCacheInterface
{
    public function read(): ?array
    {
        return null;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function write(array $config): void
    {
    }
}
