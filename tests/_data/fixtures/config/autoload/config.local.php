<?php

declare(strict_types=1);

use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\Tests\Unit\ProvidersCollectionIntegrationTest;

return [
    AurynConfig::ALIASES => [
        ProvidersCollectionIntegrationTest::CONFIG_KEY_1 => 'local config',
    ],
];
