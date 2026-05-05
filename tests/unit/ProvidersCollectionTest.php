<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use Auryn\Injector;
use Auryn\Test\SharedAliasedInterface;
use Auryn\Test\SharedClass;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Config\NodeManipulationInterface;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ProvidersCacheInterface;
use ItalyStrap\Empress\ProvidersCollection;
use ItalyStrap\Empress\Tests\Modules\ModuleStub1;
use ItalyStrap\Empress\Tests\UnitTestCase;

final class ProvidersCollectionTest extends UnitTestCase
{
    private function makeInstance(
        iterable $providers = [],
        ?ProvidersCacheInterface $cache = null,
        ?ConfigInterface $config = null
    ): ProvidersCollection {
        return new ProvidersCollection(
            new Injector(),
            $config ?: new Config(),
            $cache,
            $providers
        );
    }

    public function testAggregatePopulatesConfig(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            static fn(): array => [
                AurynConfig::ALIASES => [
                    'InterfaceName' => 'ClassName',
                ],
            ],
        ], null, $config);

        $sut->aggregate();

        $this->assertSame('ClassName', $config->get(AurynConfig::ALIASES . '.InterfaceName'));
    }

    public function testConstructorRequiresConfigWithNodeManipulationSupport(): void
    {
        $config = $this->prophesize(ConfigInterface::class)->reveal();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(NodeManipulationInterface::class);

        new ProvidersCollection(new Injector(), $config);
    }

    public function testAggregateAppendsListSectionsAndReplacesMapSectionsRecursively(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            static fn(): array => [
                AurynConfig::PROXY => [
                    'FirstProxy',
                    'SecondProxy',
                ],
                AurynConfig::SHARING => [
                    'FirstShared',
                    'SecondShared',
                ],
                AurynConfig::ALIASES => [
                    'InterfaceName' => 'FirstClass',
                    15 => 'first numeric value',
                ],
                AurynConfig::DEFINITIONS => [
                    'ServiceName' => [
                        ':overridden' => 'first',
                        ':kept' => 'kept',
                    ],
                ],
            ],
            static fn(): array => [
                AurynConfig::PROXY => [
                    'SecondProxy',
                    'FirstProxy',
                ],
                AurynConfig::SHARING => [
                    'SecondShared',
                    'FirstShared',
                ],
                AurynConfig::ALIASES => [
                    'InterfaceName' => 'SecondClass',
                    15 => 'second numeric value',
                ],
                AurynConfig::DEFINITIONS => [
                    'ServiceName' => [
                        ':overridden' => 'second',
                    ],
                ],
            ],
        ], null, $config);

        $sut->aggregate();

        $this->assertSame([
            'FirstProxy',
            'SecondProxy',
        ], $config->get(AurynConfig::PROXY));
        $this->assertSame([
            'FirstShared',
            'SecondShared',
        ], $config->get(AurynConfig::SHARING));
        $this->assertSame('SecondClass', $config->get(AurynConfig::ALIASES . '.InterfaceName'));
        $this->assertSame('second numeric value', $config->get([AurynConfig::ALIASES, 15]));
        $this->assertSame(
            [
                ':overridden' => 'second',
                ':kept' => 'kept',
            ],
            $config->get(AurynConfig::DEFINITIONS . '.ServiceName')
        );
    }

    public function testAggregateUsesAppendToForListSections(): void
    {
        $config = new class extends Config {
            /**
             * @var array<int, array{0: mixed, 1: mixed}>
             */
            public array $appendCalls = [];

            public function appendTo($key, $value): bool
            {
                $this->appendCalls[] = [$key, $value];

                return parent::appendTo($key, $value);
            }
        };

        $sut = $this->makeInstance([
            static fn(): array => [
                AurynConfig::PROXY => [
                    'FirstProxy',
                ],
                AurynConfig::SHARING => [
                    'FirstShared',
                ],
            ],
            static fn(): array => [
                AurynConfig::PROXY => [
                    'SecondProxy',
                ],
                AurynConfig::SHARING => [
                    'SecondShared',
                ],
            ],
        ], null, $config);

        $sut->aggregate();

        $this->assertSame([
            [
                AurynConfig::PROXY,
                [
                    'FirstProxy',
                    'SecondProxy',
                ],
            ],
            [
                AurynConfig::SHARING,
                [
                    'FirstShared',
                    'SecondShared',
                ],
            ],
        ], $config->appendCalls);
    }

    public function testAggregateAcceptsTraversableProvidersYieldingConfigurationArrays(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            static function (): \Generator {
                yield [
                    AurynConfig::SHARING => [
                        'FirstShared',
                    ],
                ];

                yield [
                    AurynConfig::SHARING => [
                        'SecondShared',
                    ],
                ];
            },
        ], null, $config);

        $sut->aggregate();

        $this->assertSame([
            'FirstShared',
            'SecondShared',
        ], $config->get(AurynConfig::SHARING));
    }

    public function testAggregateAcceptsInvokableObjectProviders(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            new class {
                public function __invoke(): array
                {
                    return [
                        'custom' => [
                            'key' => 'value',
                        ],
                    ];
                }
            },
        ], null, $config);

        $sut->aggregate();

        $this->assertSame('value', $config->get('custom.key'));
    }

    public function testAggregateAcceptsClassStringAndArrayCallableProviders(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            ModuleStub1::class,
            [ModuleStub1::class, '__invoke'],
        ], null, $config);

        $sut->aggregate();

        $this->assertSame(
            SharedClass::class,
            $config->get([AurynConfig::ALIASES, SharedAliasedInterface::class])
        );
    }

    public function testAggregateDoesNotExecuteProvidersWhenCacheReadSucceeds(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            public bool $written = false;

            public function read(ConfigInterface $config): bool
            {
                $config->merge([
                    'from_cache' => true,
                ]);

                return true;
            }

            public function write(ConfigInterface $config): void
            {
                $this->written = true;
            }
        };

        $config = new Config();
        $sut = $this->makeInstance([
            static function (): array {
                throw new \RuntimeException('Provider should not be executed');
            },
        ], $cache, $config);

        $sut->aggregate();

        $this->assertTrue($config->get('from_cache'));
        $this->assertFalse($cache->written);
    }

    public function testAggregateWritesCacheWhenEnabledByProviders(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            /**
             * @var array<array-key, mixed>
             */
            public array $writtenConfig = [];

            public function read(ConfigInterface $config): bool
            {
                return false;
            }

            public function write(ConfigInterface $config): void
            {
                $this->writtenConfig = $config->toArray();
            }
        };

        $sut = $this->makeInstance([
            static fn(): array => [
                ProvidersCacheInterface::ENABLE_CACHE => true,
                'key' => 'value',
            ],
        ], $cache);

        $sut->aggregate();

        $this->assertSame('value', $cache->writtenConfig['key']);
    }

    public function testAggregateDoesNotWriteCacheWhenCacheIsDisabled(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            public bool $written = false;

            public function read(ConfigInterface $config): bool
            {
                return false;
            }

            public function write(ConfigInterface $config): void
            {
                $this->written = true;
            }
        };

        $sut = $this->makeInstance([
            static fn(): array => [
                'key' => 'value',
            ],
        ], $cache);

        $sut->aggregate();

        $this->assertFalse($cache->written);
    }

    public function testAggregateThrowsWhenProviderReturnsInvalidResult(): void
    {
        $sut = $this->makeInstance([
            static fn(): string => 'invalid',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return an array or iterable of arrays');

        $sut->aggregate();
    }

    public function testAggregateThrowsWhenProviderYieldsInvalidResult(): void
    {
        $sut = $this->makeInstance([
            static function (): \Generator {
                yield 'invalid';
            },
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('yielded string, expected array');

        $sut->aggregate();
    }

    public function testAggregateWrapsProviderExecutionErrors(): void
    {
        $sut = $this->makeInstance([
            static function (): array {
                throw new \RuntimeException('Provider failed');
            },
        ]);

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Provider failed');

        $sut->aggregate();
    }
}
