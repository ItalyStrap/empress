<?php

declare(strict_types=1);

use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Tests\Unit\ProvidersCollectionIntegrationTest;

return [
    AurynConfig::ALIASES => [
        ProvidersCollectionIntegrationTest::CONFIG_KEY_3 => 'test.global.php',
    ],
];
