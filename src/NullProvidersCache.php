<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Config\ConfigInterface;

/**
 * Default cache: never reads, never writes.
 * Install italystrap/empress-cache for a file based implementation.
 */
final class NullProvidersCache implements ProvidersCacheInterface
{
    /**
     * @param ConfigInterface<array-key, mixed> $config
     */
    public function read(ConfigInterface $config): bool
    {
        return false;
    }

    /**
     * @param ConfigInterface<array-key, mixed> $config
     */
    public function write(ConfigInterface $config): void
    {
    }
}
