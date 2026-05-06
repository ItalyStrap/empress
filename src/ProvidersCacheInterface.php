<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Config\ConfigInterface;

interface ProvidersCacheInterface
{
    public const ENABLE_CACHE = 'config_cache_enabled';

    public const CACHE_FILEMODE = 'config_cache_filemode';

    public const CACHE_PATH = 'cache_config_path';

    /**
     * @param ConfigInterface<array-key, mixed> $config
     */
    public function read(ConfigInterface $config): bool;

    /**
     * @param ConfigInterface<array-key, mixed> $config
     */
    public function write(ConfigInterface $config): void;
}
