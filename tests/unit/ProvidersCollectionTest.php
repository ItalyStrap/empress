<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Config\ConfigInterface;
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
            ],
            $this->cachedConfigFile
        );
    }

    public function testShouldBeInstantiable()
    {
        $this->assertInstanceOf(ConfigInterface::class, $this->makeConfig());

        $this->config
            ->merge(Argument::cetera())
            ->shouldBeCalledTimes(1);

        $this->config
            ->toArray()
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'key' => 'value',
            ]);

        $sut = $this->makeInstance();

        $this->assertFileExists($this->cachedConfigFile);
        $this->assertFileIsReadable($this->cachedConfigFile);

        $file = require $this->cachedConfigFile;

        $this->assertIsArray($file);
        $this->assertArrayHasKey('key', $file);
    }
}
