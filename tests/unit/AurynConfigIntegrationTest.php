<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ProxyFactoryInterface;
use ItalyStrap\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Tests\SomeConcrete;
use ItalyStrap\Tests\SomeInterface;
use ItalyStrap\Tests\UnitTestCase;
use Prophecy\Argument;

class AurynConfigIntegrationTest extends UnitTestCase
{
    private function makeInstance(array $config = []): AurynConfig
    {
        return new AurynConfig($this->realInjector, ConfigFactory::make($config), $this->realProxyFactory);
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

    public function testItShouldShare(): void
    {
        $aurynConfig = $this->makeInstance(
            [
                AurynConfig::SHARING => [
                    SomeConcrete::class,
                ],
            ]
        );

        $aurynConfig->resolve();

        $shared = $this->realInjector->make(SomeConcrete::class);
        $this->assertSame($shared, $this->realInjector->make(SomeConcrete::class));
    }

    public function testItShouldProxy(): void
    {
        $this->proxyFactory->__invoke(Argument::type('string'), Argument::type('callable'))
            ->willReturn(new class
            {
                public function render(): string
                {
                    return 'DifferentConcrete';
                }
            })
            ->shouldBeCalledTimes(1);

        $this->realProxyFactory = $this->proxyFactory->reveal();
        $sut = $this->makeInstance(
            [
                AurynConfig::PROXY => [
                    SomeConcrete::class
                ],
            ]
        );

        $sut->resolve();

        /** @var SomeConcrete $concrete */
        $concrete = $this->realInjector->make(SomeConcrete::class);
        $this->assertSame('DifferentConcrete', $concrete->render());
    }
}
