<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests;

final class AddProcessedFlag
{
    /**
     * @param array<array-key, mixed> $config
     * @return array<array-key, mixed>
     */
    public function __invoke(array $config): array
    {
        $config['processed_by'] = self::class;

        return $config;
    }
}
