<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Config\Config;
use ItalyStrap\Empress\NullProvidersCache;
use ItalyStrap\Empress\Tests\UnitTestCase;

final class NullProvidersCacheTest extends UnitTestCase
{
    public function testReadReturnsFalseAndLeavesConfigUntouched(): void
    {
        $config = new Config([
            'key' => 'value',
        ]);

        $this->assertFalse((new NullProvidersCache())->read($config));
        $this->assertSame(['key' => 'value'], $config->toArray());
    }

    public function testWriteLeavesConfigUntouched(): void
    {
        $config = new Config([
            'key' => 'value',
        ]);

        (new NullProvidersCache())->write($config);

        $this->assertSame(['key' => 'value'], $config->toArray());
    }
}
