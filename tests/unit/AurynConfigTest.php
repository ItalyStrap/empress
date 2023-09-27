<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\AurynConfigInterface;
use ItalyStrap\Empress\Extension;
use ItalyStrap\Tests\UnitTestCase;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;

class AurynConfigTest extends UnitTestCase
{
    protected function makeInstance(array $config = []): AurynConfig
    {
        return new AurynConfig($this->makeInjector(), ConfigFactory::make($config));
    }

    public function shareProvider()
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

        $this->injector->share(Argument::any())->will(function ($args) use ($expected) {
            Assert::assertEquals($expected, $args[0], '');
        });

        $sut = $this->makeInstance(
            [
                AurynConfig::SHARING    => [
                    $expected,
                ],
            ]
        );

        $sut->resolve();
    }

    public function testItShouldProxy(): void
    {

        $expected = 'SomeClassProxies';

        $this->injector->proxy(
            Argument::type('string'),
            Argument::type('callable')
        )->will(function ($args) use ($expected) {
            Assert::assertEquals($expected, $args[0], '');
        });

        $sut = $this->makeInstance(
            [
                AurynConfig::PROXY  => [
                    $expected,
                ],
            ]
        );

        $sut->resolve();
    }

    public function testItShouldAlias(): void
    {

        $this->injector
            ->alias(Argument::type('string'), Argument::type('string'))
            ->will(function ($args) {
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

        $sut->resolve();
    }

    public function testItShouldDefine(): void
    {

        $this->injector
            ->define(Argument::type('string'), Argument::type('array'))
            ->will(function ($args) {
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

        $sut->resolve();
    }

    public function testItShouldDefineParam(): void
    {

        $param_expected = new class {
        };

        $this->injector
            ->defineParam(Argument::type('string'), Argument::any())
            ->will(function ($args) use ($param_expected) {
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

        $sut->resolve();
    }

    public function testItShouldDelegate(): void
    {

        $factory_delegation = function () {
            return new class {
            };
        };

        $this->injector
            ->delegate(Argument::type('string'), Argument::any())
            ->will(function ($args) use ($factory_delegation) {
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

        $sut->resolve();
    }

    public function testItShouldPrepare(): void
    {

        $preparation_callback = function ($class, $injector) {
            Assert::assertEquals('ClassName', $class, '');
            Assert::assertInstanceOf(Injector::class, $injector, '');
        };

        $test = $this->prophet;

        $this->injector
            ->prepare(Argument::type('string'), Argument::any())
            ->will(function ($args) use ($preparation_callback, $test) {
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

        $sut->resolve();
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

        $sut->walk('Test', function (string $value, $key) {
            Assert::assertStringContainsString($value, 'ClassName', '');
            Assert::assertStringContainsString($key, 'Key', '');
        });
    }

    public function testItShouldExtendFakeClass(): void
    {

        $this
            ->injector
            ->share(Argument::type('string'), Argument::any())
            ->will(function ($args) {
                Assert::assertStringContainsString('ClassName', $args[0], '');
            });

        $this->injector->make(Argument::type('string'), Argument::type('array'))
            ->will(function ($args) {
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

        $extension = $this->prophet->prophesize(Extension::class);

        $extension->name()->willReturn('ExtensionName');

        $extension->execute(Argument::exact($sut))->will(function ($args) {
        });

        $sut->extend($extension->reveal());

        $sut->resolve();
    }

    public function testItShouldExtendRealClass(): void
    {

        $this->injector->share(Argument::type('string'), Argument::any())
            ->will(function ($args) {
                Assert::assertStringContainsString('ClassName', $args[0], '');
            });

        $this->injector->make(Argument::type('string'), Argument::type('array'))
            ->will(function ($args) {
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

            public function execute(AurynConfigInterface $application)
            {
                $application->walk(self::SUBSCRIBERS, $this);
            }

            public function __invoke(string $class, $index_or_optionName, Injector $injector)
            {
                Assert::assertStringContainsString($class, 'ClassName', '');
                $injector->share($class);
                $injector->make($class, []);
            }
        });

        $sut->resolve();
    }

    public function testOldClassNameShouldBeAliasedCorrectly(): void
    {
        /**
         * New name is AurynConfig::class
         */
        $auryn_config = new \ItalyStrap\Empress\AurynResolver(new Injector(), ConfigFactory::make([]));
        $auryn_config->resolve();
    }
}
