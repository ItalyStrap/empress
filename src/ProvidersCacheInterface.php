<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Config\ConfigInterface;

interface ProvidersCacheInterface
{
    /**
     * @param ConfigInterface<array-key, mixed> $config
     */
    public function read(ConfigInterface $config): bool;

    /**
     * @param ConfigInterface<array-key, mixed> $config
     */
    public function write(ConfigInterface $config): void;
}
