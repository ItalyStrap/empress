<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Empress\NullProvidersCache;
use ItalyStrap\Empress\Tests\UnitTestCase;

final class NullProvidersCacheTest extends UnitTestCase
{
    public function testReadReturnsNull(): void
    {
        $this->assertNull((new NullProvidersCache())->read());
    }

    public function testReadReturnsNullAfterWrite(): void
    {
        $sut = new NullProvidersCache();

        $sut->write([
            'key' => 'value',
        ]);

        $this->assertNull($sut->read());
    }
}
