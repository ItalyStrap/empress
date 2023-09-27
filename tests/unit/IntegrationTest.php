<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Tests\SomeConcrete;
use ItalyStrap\Tests\SomeInterface;
use ItalyStrap\Tests\UnitTestCase;

class IntegrationTest extends UnitTestCase
{
    public function testItShouldAlias(): void
    {
        $injector = new Injector();
        $aurynConfig = new AurynConfig(
            $injector,
            ConfigFactory::make(
                [
                    AurynConfig::ALIASES => [
                        SomeInterface::class => SomeConcrete::class,
                    ],
                ]
            )
        );

        $aurynConfig->resolve();

        $this->assertInstanceOf(SomeConcrete::class, $injector->make(SomeInterface::class));
        $this->assertInstanceOf(SomeConcrete::class, $injector->make(SomeConcrete::class));
        $object = $injector->make(ConcreteNeedsSomeInterface::class);
        $actual = $object->someInterface();
        $this->assertInstanceOf(SomeConcrete::class, $actual);
        $this->assertSame('SomeConcrete', $actual->render());
    }
}
