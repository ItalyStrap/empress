<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Empress\ProxyFactory;
use ItalyStrap\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Tests\SomeConcrete;
use ItalyStrap\Tests\SomeInterface;
use ItalyStrap\Tests\UnitTestCase;

class AurynConfigIntegrationTest extends UnitTestCase
{
    private function makeInstance(array $config = []): AurynConfig
    {
        return new AurynConfig($this->realInjector, ConfigFactory::make($config), new ProxyFactory());
    }

    public function testItShouldAlias(): void
    {
        $aurynConfig = $this->makeInstance(
            [
                AurynConfig::ALIASES => [
                    SomeInterface::class => SomeConcrete::class,
                ],
            ]
        );

        $aurynConfig->resolve();

        $this->assertInstanceOf(SomeConcrete::class, $this->realInjector->make(SomeInterface::class));
        $this->assertInstanceOf(SomeConcrete::class, $this->realInjector->make(SomeConcrete::class));
        $object = $this->realInjector->make(ConcreteNeedsSomeInterface::class);
        $actual = $object->someInterface();
        $this->assertInstanceOf(SomeConcrete::class, $actual);
        $this->assertSame('SomeConcrete', $actual->render());
    }
}
