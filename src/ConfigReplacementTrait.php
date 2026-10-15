<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Config\ConfigInterface;

trait ConfigReplacementTrait
{
    /**
     * @todo Replace with ConfigInterface::replace() when available.
     *
     * @param ConfigInterface<array-key, mixed> $config
     * @param array<array-key, mixed> $values
     */
    private function replaceConfig(ConfigInterface $config, array $values): void
    {
        // @phpstan-ignore-next-line Config supports exchangeArray(), but ConfigInterface does not expose it yet.
        $config->exchangeArray($values);
    }
}
