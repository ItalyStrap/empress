<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Empress\ProvidersCollection;
use ItalyStrap\Tests\UnitTestCase;
use Prophecy\Argument;

class ProvidersCollectionTest extends UnitTestCase
{
    private function makeInstance(): ProvidersCollection
    {
        return new ProvidersCollection(
            $this->makeInjector(),
            $this->makeConfig(),
            [
            ]
        );
    }

    public function testShouldBeInstantiable()
    {

        $this->config
            ->merge(Argument::cetera())
            ->shouldBeCalledTimes(1);

        $this->config
            ->toArray()
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'key' => 'value',
            ]);

        $this->config
            ->get('config_cache_enabled', false)
            ->willReturn(true);

        $this->config
            ->get('config_cache_filemode', Argument::type('int'))
            ->will(function ($args): int {
                return (int)$args[1];
            });

        $this->config
            ->get('cache_config_path', Argument::type('null'))
            ->willReturn($this->cachedConfigFile);

        $sut = $this->makeInstance();
        $sut->build();

        $this->assertFileExists($this->cachedConfigFile);
        $this->assertFileIsReadable($this->cachedConfigFile);

        $file = require $this->cachedConfigFile;

        $this->assertIsArray($file);
        $this->assertArrayHasKey('key', $file);
    }
}
