<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use Auryn\Injector;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\AurynConfigInterface;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Empress\Extension;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Empress\ProvidersCacheInterface;
use ItalyStrap\Empress\ProxyFactoryInterface;
use ItalyStrap\Empress\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Empress\Tests\ContainerBuilderExtensionStub;
use ItalyStrap\Empress\Tests\SomeConcrete;
use ItalyStrap\Empress\Tests\SomeInterface;
use ItalyStrap\Empress\Tests\UnitTestCase;
use Psr\Container\ContainerInterface;

final class ContainerBuilderTest extends UnitTestCase
{
    public function testAddProviderIsFluentAndBuildWiresProviderConfig(): void
    {
        $builder = new ContainerBuilder();

        $result = $builder->addProvider(new class implements ModuleInterface {
            public function __invoke(): iterable
            {
                return [
                    AurynConfig::ALIASES => [
                        SomeInterface::class => SomeConcrete::class,
                    ],
                    AurynConfig::SHARING => [
                        SomeConcrete::class,
                    ],
                ];
            }
        });

        $this->assertSame($builder, $result);

        $container = $builder->build();

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertSame($container, $container->get(ContainerInterface::class));

        $service = $container->get(SomeInterface::class);
        $this->assertInstanceOf(SomeConcrete::class, $service);
        $this->assertSame('SomeConcrete', $service->render());
    }

    public function testAddModuleIsFluentAliasForAddProvider(): void
    {
        $builder = new ContainerBuilder();

        $result = $builder->addModule(static fn(): array => [
            AurynConfig::ALIASES => [
                SomeInterface::class => SomeConcrete::class,
            ],
        ]);

        $this->assertSame($builder, $result);
        $this->assertInstanceOf(SomeConcrete::class, $builder->build()->get(SomeInterface::class));
    }

    public function testExtendIsFluentAndAppliesExtensionInstance(): void
    {
        $extension = new class implements Extension {
            public bool $executed = false;

            public function name(): string
            {
                return self::class;
            }

            public function execute(AurynConfigInterface $application): void
            {
                $this->executed = true;
            }
        };
        $builder = new ContainerBuilder();

        $result = $builder->extend($extension);
        $builder->build();

        $this->assertSame($builder, $result);
        $this->assertTrue($extension->executed);
    }

    public function testExtendAcceptsExtensionClassString(): void
    {
        $config = new Config([
            'container_builder_test_aliases' => [
                SomeInterface::class => SomeConcrete::class,
            ],
        ]);
        $builder = new ContainerBuilder(null, $config);

        $builder->extend(ContainerBuilderExtensionStub::class);
        $container = $builder->build();

        $this->assertInstanceOf(SomeConcrete::class, $container->get(SomeInterface::class));
    }

    public function testBuildMergesMultipleModulesAndSharesAliasedService(): void
    {
        $builder = new ContainerBuilder();

        $builder->addProvider(new class implements ModuleInterface {
            public function __invoke(): iterable
            {
                return [
                    AurynConfig::ALIASES => [
                        SomeInterface::class => SomeConcrete::class,
                    ],
                ];
            }
        });

        $builder->addProvider(new class implements ModuleInterface {
            public function __invoke(): iterable
            {
                return [
                    AurynConfig::SHARING => [
                        SomeConcrete::class,
                    ],
                ];
            }
        });

        $container = $builder->build();

        $first = $container->get(SomeInterface::class);
        $second = $container->get(SomeConcrete::class);

        $this->assertInstanceOf(SomeConcrete::class, $first);
        $this->assertSame($first, $second, 'Aliased service should be shared across resolutions');
    }

    public function testBuildDelegationsCanRequestContainerInterface(): void
    {
        $builder = new ContainerBuilder();

        $builder->addProvider(new class implements ModuleInterface {
            public function __invoke(): iterable
            {
                return [
                    AurynConfig::ALIASES => [
                        SomeInterface::class => SomeConcrete::class,
                    ],
                ];
            }
        });

        $builder->addProvider(new class implements ModuleInterface {
            public function __invoke(): iterable
            {
                return [
                    AurynConfig::DELEGATIONS => [
                        ConcreteNeedsSomeInterface::class
                            => static function (ContainerInterface $container): ConcreteNeedsSomeInterface {
                                $some = $container->get(SomeInterface::class);
                                return new ConcreteNeedsSomeInterface($some);
                            }
                    ],
                ];
            }
        });

        $container = $builder->build();

        $service = $container->get(ConcreteNeedsSomeInterface::class);
        $this->assertInstanceOf(
            ConcreteNeedsSomeInterface::class,
            $service,
            'Service should be an instance of ConcreteNeedsSomeInterface'
        );
        $this->assertInstanceOf(
            SomeConcrete::class,
            $service->someInterface(),
            'Dependency should be an instance of SomeConcrete'
        );
    }

    public function testBuildUsesCustomInjectorAndSharesIt(): void
    {
        $injector = new Injector();
        $builder = new ContainerBuilder($injector);

        $container = $builder->build();

        $this->assertSame($injector, $injector->make(Injector::class));
        $this->assertSame($container, $injector->make(ContainerInterface::class));
    }

    public function testBuildPopulatesCustomConfig(): void
    {
        $config = new Config();
        $builder = new ContainerBuilder(null, $config);

        $builder->addProvider(static fn(): array => [
            'custom' => [
                'key' => 'value',
            ],
        ]);

        $builder->build();

        $this->assertSame('value', $config->get('custom.key'));
    }

    public function testBuildPassesCustomCacheToProvidersCollection(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            public bool $written = false;

            public function read(ConfigInterface $config): bool
            {
                $config->merge([
                    AurynConfig::ALIASES => [
                        SomeInterface::class => SomeConcrete::class,
                    ],
                ]);

                return true;
            }

            public function write(ConfigInterface $config): void
            {
                $this->written = true;
            }
        };
        $builder = new ContainerBuilder(null, null, $cache);
        $builder->addProvider(static function (): array {
            throw new \RuntimeException('Provider should not be executed when cache is warm');
        });

        $container = $builder->build();

        $this->assertInstanceOf(SomeConcrete::class, $container->get(SomeInterface::class));
        $this->assertFalse($cache->written);
    }

    public function testBuildPassesCustomProxyFactoryToAurynConfig(): void
    {
        $factory = new class implements ProxyFactoryInterface {
            public bool $called = false;

            public function __invoke(string $className, callable $callback): object
            {
                $this->called = true;

                return new class extends SomeConcrete {
                    public function render(): string
                    {
                        return 'ProxiedConcrete';
                    }
                };
            }
        };
        $builder = new ContainerBuilder(null, null, null, $factory);
        $builder->addProvider(static fn(): array => [
            AurynConfig::PROXY => [
                SomeConcrete::class,
            ],
        ]);

        /** @var SomeConcrete $service */
        $service = $builder->build()->get(SomeConcrete::class);

        $this->assertTrue($factory->called);
        $this->assertSame('ProxiedConcrete', $service->render());
    }

    public function testBuildIgnoresProxyConfigurationWhenProxyFactoryIsMissing(): void
    {
        $builder = new ContainerBuilder();
        $builder->addProvider(static fn(): array => [
            AurynConfig::PROXY => [
                SomeConcrete::class,
            ],
        ]);

        /** @var SomeConcrete $service */
        $service = $builder->build()->get(SomeConcrete::class);

        $this->assertSame('SomeConcrete', $service->render());
    }
}
