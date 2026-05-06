<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ProxyFactoryInterface;
use ItalyStrap\Empress\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Empress\Tests\SomeConcrete;
use ItalyStrap\Empress\Tests\SomeInterface;
use ItalyStrap\Empress\Tests\UnitTestCase;
use Prophecy\Argument;

final class AurynConfigIntegrationTest extends UnitTestCase
{
    private function makeInstance(array $config = []): AurynConfig
    {
        return new AurynConfig($this->realInjector, (new ConfigFactory())->make($config), $this->realProxyFactory);
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

        $aurynConfig->apply();

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

        $aurynConfig->apply();

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

        $sut->apply();

        /** @var SomeConcrete $concrete */
        $concrete = $this->realInjector->make(SomeConcrete::class);
        $this->assertSame('DifferentConcrete', $concrete->render());
    }
}
