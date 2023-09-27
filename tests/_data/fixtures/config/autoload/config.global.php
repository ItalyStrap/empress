<?php

declare(strict_types=1);

use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Tests\Unit\ProvidersCollectionIntegrationTest;

return [
    AurynConfig::ALIASES => [
        ProvidersCollectionIntegrationTest::CONFIG_KEY_1 => 'global config',
    ],
    AurynConfig::SHARING => [
    ],
];
