<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use Auryn\Injector;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\AurynConfigInterface;
use ItalyStrap\Empress\Extension;
use ItalyStrap\Empress\ProxyFactory;
use ItalyStrap\Empress\ProxyFactoryInterface;
use ItalyStrap\Empress\Tests\SomeConcrete;
use ItalyStrap\Empress\Tests\SomeExtension;
use ItalyStrap\Empress\Tests\UnitTestCase;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;

final class AurynConfigTest extends UnitTestCase
{
    protected function makeInstance(array $config = []): AurynConfig
    {
        return new AurynConfig($this->makeInjector(), (new ConfigFactory())->make($config), $this->makeProxyFactory());
    }

//    public function testItShouldProxy(): void
//    {
//        $mockProxyFactory = $this->prophesize(ProxyFactoryInterface::class);
//        $mockProxyFactory->__invoke(Argument::type('string'), Argument::type('callable'))
//            ->shouldBeCalledTimes(1);
//
//        $this->proxyFactory = $mockProxyFactory->reveal();
//        $sut = $this->makeInstance(
//            [
//                AurynConfig::PROXY => [
//                    SomeConcrete::class
//                ],
//            ]
//        );
//
//        $sut->$this->apply();
//
//        $concrete = $this->realInjector->make(SomeConcrete::class);
//    }

    public function testItShouldProxy01(): void
    {

        $expected = 'SomeClassProxies';

        $this->injector->proxy(
            Argument::type('string'),
            Argument::type('callable')
        )->will(function ($args) use ($expected): void {
            Assert::assertEquals($expected, $args[0], '');
        });

        $sut = $this->makeInstance(
            [
                AurynConfig::PROXY  => [
                    $expected,
                ],
            ]
        );

        $sut->apply();
    }

    public function shareProvider(): iterable
    {
        return [
            'ClassName'     => [
                'SomeClassName'
            ],
            'ClassInstance' => [
                new class {
                }
            ],
        ];
    }

    /**
     * @dataProvider shareProvider()
     */
    public function testItShouldShare($expected): void
    {

        $this->injector->share(Argument::any())->will(function ($args) use ($expected): void {
            Assert::assertEquals($expected, $args[0], '');
        });

        $sut = $this->makeInstance(
            [
                AurynConfig::SHARING    => [
                    $expected,
                ],
            ]
        );

        $sut->apply();
    }

    public function testItShouldAlias(): void
    {

        $this->injector
            ->alias(Argument::type('string'), Argument::type('string'))
            ->will(function ($args): void {
                Assert::assertEquals('InterfaceName', $args[0], '');
                Assert::assertEquals('ClassName', $args[1], '');
            });

        $sut = $this->makeInstance(
            [
                AurynConfig::ALIASES    => [
                    'InterfaceName' => 'ClassName',
                ],
            ]
        );

        $sut->apply();
    }

    public function testItShouldDefine(): void
    {

        $this->injector
            ->define(Argument::type('string'), Argument::type('array'))
            ->will(function ($args): void {
                Assert::assertEquals('ClassName', $args[0], '');
                Assert::assertArrayHasKey(':config', $args[1], '');
            });

        $sut = $this->makeInstance(
            [
                AurynConfig::DEFINITIONS    => [
                    'ClassName' => [
                        ':config'   => new class {
                        }
                    ],
                ],
            ]
        );

        $sut->apply();
    }

    public function testItShouldDefineParam(): void
    {

        $param_expected = new class {
        };

        $this->injector
            ->defineParam(Argument::type('string'), Argument::any())
            ->will(function ($args) use ($param_expected): void {
                Assert::assertEquals(':config', $args[0], '');
                Assert::assertEquals($param_expected, $args[1], '');
            });

        $sut = $this->makeInstance(
            [
                AurynConfig::DEFINE_PARAM   => [
                    ':config'   => $param_expected,
                ],
            ]
        );

        $sut->apply();
    }

    public function testItShouldDelegate(): void
    {

        $factory_delegation = fn(): object => new class {
        };

        $this->injector
            ->delegate(Argument::type('string'), Argument::any())
            ->will(function ($args) use ($factory_delegation): void {
                Assert::assertEquals(':config', $args[0], '');
                Assert::assertEquals($factory_delegation, $args[1], '');
                Assert::assertIsCallable($args[1], '');
            });

        $sut = $this->makeInstance(
            [
                AurynConfig::DELEGATIONS    => [
                    ':config'   => $factory_delegation,
                ],
            ]
        );

        $sut->apply();
    }

    public function testItShouldPrepare(): void
    {

        $preparation_callback = function ($class, $injector): void {
            Assert::assertEquals('ClassName', $class, '');
            Assert::assertInstanceOf(Injector::class, $injector, '');
        };

        $test = $this;

        $this->injector
            ->prepare(Argument::type('string'), Argument::any())
            ->will(function ($args) use ($preparation_callback, $test): void {
                Assert::assertEquals('ClassName', $args[0], '');
                Assert::assertEquals($preparation_callback, $args[1], '');
                Assert::assertIsCallable($args[1], '');
                \call_user_func(
                    $args[1],
                    'ClassName',
                    $test->prophesize(Injector::class)->reveal()
                );
            });

        $sut = $this->makeInstance(
            [
                AurynConfig::PREPARATIONS   => [
                    'ClassName' => $preparation_callback,
                ],
            ]
        );

        $sut->apply();
    }

    public function testItShouldWalk(): void
    {
        $sut = $this->makeInstance(
            [
                'Test'  => [
                    'Key'   => 'ClassName',
                ],
            ]
        );

        $sut->walk('Test', function (string $value, $key): void {
            Assert::assertStringContainsString($value, 'ClassName', '');
            Assert::assertStringContainsString($key, 'Key', '');
        });
    }

    public function testItShouldExtendFakeClass(): void
    {

        $this
            ->injector
            ->share(Argument::type('string'), Argument::any())
            ->will(function ($args): void {
                Assert::assertStringContainsString('ClassName', $args[0], '');
            });

        $this->injector->make(Argument::type('string'), Argument::type('array'))
            ->will(function ($args): void {
                Assert::assertStringContainsString('ClassName', $args[0], '');
            });

        $sut = $this->makeInstance(
            [
                'subscribers'   => [
                    'ClassName',
                    'option-name'   => 'ClassName',
                ],
            ]
        );

        $extension = $this->prophesize(Extension::class);

        $extension->name()->willReturn('ExtensionName');

        $extension->execute(Argument::exact($sut))->will(function ($args): void {
        });

        $sut->extend($extension->reveal());

        $sut->apply();
    }

    public function testItShouldExtendRealClass(): void
    {

        $this->injector->share(Argument::type('string'), Argument::any())
            ->will(function ($args): void {
                Assert::assertStringContainsString('ClassName', $args[0], '');
            });

        $this->injector->make(Argument::type('string'), Argument::type('array'))
            ->will(function ($args): void {
                Assert::assertStringContainsString('ClassName', $args[0], '');
            });

        $sut = $this->makeInstance(
            [
                'subscribers'   => [
                    'ClassName',
                    'option-name'   => 'ClassName',
                ],
            ]
        );

        $sut->extend(new class implements Extension {
            /** @var string */
            public const SUBSCRIBERS = 'subscribers';

            public function name(): string
            {
                return self::SUBSCRIBERS;
            }

            public function execute(AurynConfigInterface $application): void
            {
                $application->walk(self::SUBSCRIBERS, $this);
            }

            public function __invoke(string $class, $index_or_optionName, Injector $injector): void
            {
                Assert::assertStringContainsString($class, 'ClassName', '');
                $injector->share($class);
                $injector->make($class, []);
            }
        });

        $sut->apply();
    }

    public function testItShouldExtendClassString(): void
    {
        $sut = new AurynConfig(new Injector(), (new ConfigFactory())->make());
        $sut->extend(SomeExtension::class);
        $this->expectOutputString(SomeExtension::class);
        $sut->apply();
    }

    public function testItShouldNotExtend(): void
    {
        $sut = new AurynConfig(new Injector(), (new ConfigFactory())->make());
        $this->expectException(\InvalidArgumentException::class);
        $sut->extend('SomeGenericClass');
    }

    public function testOldClassNameShouldBeAliasedCorrectly(): void
    {
        /**
         * New name is AurynConfig::class
         */
        $auryn_config = new \ItalyStrap\Empress\AurynResolver(new Injector(), (new ConfigFactory())->make([]));
        $auryn_config->apply();
    }
}
