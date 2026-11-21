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
use ItalyStrap\Empress\ProviderException;
use ItalyStrap\Empress\ProvidersCollection;
use ItalyStrap\Empress\Tests\AddProcessedFlag;
use ItalyStrap\Empress\Tests\Modules\ModuleStub1;
use ItalyStrap\Empress\Tests\UnitTestCase;

final class ProvidersCollectionTest extends UnitTestCase
{
    private function makeInstance(
        iterable $providers = [],
        ?ProvidersCacheInterface $cache = null,
        ?ConfigInterface $config = null,
        iterable $postProcessors = []
    ): ProvidersCollection {
        return new ProvidersCollection(
            new Injector(),
            $config ?: new Config(),
            $cache,
            $providers,
            $postProcessors
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

    public function testAggregateAppendsListValuesAndReplacesMapValuesRecursively(): void
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
                ],
                AurynConfig::ALIASES => [
                    'InterfaceName' => 'FirstClass',
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
                ],
                AurynConfig::ALIASES => [
                    'InterfaceName' => 'SecondClass',
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
        $this->assertSame([
            'InterfaceName' => 'SecondClass',
        ], $config->get(AurynConfig::ALIASES));
        $this->assertSame(
            [
                ':overridden' => 'second',
                ':kept' => 'kept',
            ],
            $config->get(AurynConfig::DEFINITIONS . '.ServiceName')
        );
    }

    public function testAggregateMergesMixedExternalSectionsByArrayShape(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            static fn(): array => [
                'external_section' => [
                    'FirstService',
                    'feature_flag' => 'ConditionalService',
                    'nested' => [
                        'kept' => 'kept',
                        'overridden' => 'first',
                    ],
                ],
            ],
            static fn(): array => [
                'external_section' => [
                    'FirstService',
                    'SecondService',
                    'feature_flag' => 'UpdatedConditionalService',
                    'nested' => [
                        'overridden' => 'second',
                        'added' => 'added',
                    ],
                ],
            ],
        ], null, $config);

        $sut->aggregate();

        $externalSection = $config->get('external_section');

        $this->assertSame('FirstService', $externalSection[0]);
        $this->assertSame('SecondService', $externalSection[1]);
        $this->assertSame('UpdatedConditionalService', $externalSection['feature_flag']);
        $this->assertSame([
            'kept' => 'kept',
            'overridden' => 'second',
            'added' => 'added',
        ], $externalSection['nested']);
    }

    public function testAggregateAppendsProviderListsToPreloadedConfig(): void
    {
        $config = new Config([
            'external_section' => [
                'PreloadedService',
                'feature_flag' => 'PreloadedConditionalService',
            ],
        ]);
        $sut = $this->makeInstance([
            static fn(): array => [
                'external_section' => [
                    'ProviderService',
                    'feature_flag' => 'ProviderConditionalService',
                ],
            ],
        ], null, $config);

        $sut->aggregate();

        $externalSection = $config->get('external_section');

        $this->assertSame('PreloadedService', $externalSection[0]);
        $this->assertSame('ProviderService', $externalSection[1]);
        $this->assertSame('ProviderConditionalService', $externalSection['feature_flag']);
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

            public function read(): array
            {
                return [
                    'from_cache' => true,
                ];
            }

            public function write(array $config): void
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

    public function testAggregateWritesCacheWhenCacheIsProvided(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            /**
             * @var array<array-key, mixed>
             */
            public array $writtenConfig = [];

            public function read(): ?array
            {
                return null;
            }

            public function write(array $config): void
            {
                $this->writtenConfig = $config;
            }
        };

        $sut = $this->makeInstance([
            static fn(): array => [
                'key' => 'value',
            ],
        ], $cache);

        $sut->aggregate();

        $this->assertSame('value', $cache->writtenConfig['key']);
    }

    public function testAggregateKeepsExistingConfigValuesWhenReadingFromCache(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            public function read(): array
            {
                return [
                    'from_cache' => true,
                ];
            }

            public function write(array $config): void
            {
            }
        };
        $config = new Config([
            'existing' => 'value',
        ]);

        $this->makeInstance([], $cache, $config)->aggregate();

        $this->assertSame([
            'existing' => 'value',
            'from_cache' => true,
        ], $config->toArray());
    }

    public function testAggregateDoesNotSplitTopLevelKeysContainingTheDelimiter(): void
    {
        $config = new Config();

        $this->makeInstance([
            static fn(): array => [
                'some.dotted.key' => 'value',
            ],
        ], null, $config)->aggregate();

        $this->assertSame([
            'some.dotted.key' => 'value',
        ], $config->toArray());
    }

    public function testAggregateThrowsWhenProviderReturnsInvalidResult(): void
    {
        $sut = $this->makeInstance([
            static fn(): string => 'invalid',
        ]);

        $this->expectException(ProviderException::class);
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

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('yielded string, expected array');

        $sut->aggregate();
    }

    public function testInvalidResultMessageNamesTheReturnedClass(): void
    {
        $sut = $this->makeInstance([
            static fn(): \stdClass => new \stdClass(),
        ]);

        $this->expectExceptionMessage('stdClass given');

        $sut->aggregate();
    }

    public function testInvalidYieldMessageNamesTheYieldedClass(): void
    {
        $sut = $this->makeInstance([
            static function (): \Generator {
                yield new \stdClass();
            },
        ]);

        $this->expectExceptionMessage('yielded stdClass, expected array');

        $sut->aggregate();
    }

    public function testExecutionErrorMessageNamesTheScalarProviderType(): void
    {
        $sut = $this->makeInstance([42]);

        $this->expectExceptionMessage('An error occurred when executing int:');

        $sut->aggregate();
    }

    public function testAggregateWrapsProviderExecutionErrors(): void
    {
        $sut = $this->makeInstance([
            static function (): array {
                throw new \RuntimeException('Provider failed');
            },
        ]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Provider failed');

        $sut->aggregate();
    }

    public function testProviderExceptionKeepsTheOriginalErrorAsPrevious(): void
    {
        $original = new \RuntimeException('Provider failed');
        $sut = $this->makeInstance([
            static function () use ($original): array {
                throw $original;
            },
        ]);

        try {
            $sut->aggregate();
            $this->fail('Expected provider failure');
        } catch (ProviderException $exception) {
            $this->assertSame($original, $exception->getPrevious());
            $this->assertSame(0, $exception->getCode());
        }
    }

    public function testExecutionErrorMessageNamesClassAndMethodOfArrayProvider(): void
    {
        $sut = $this->makeInstance([
            [ModuleStub1::class, 'missingMethod'],
        ]);

        $this->expectExceptionMessage(
            \sprintf('An error occurred when executing %s::missingMethod:', ModuleStub1::class)
        );

        $sut->aggregate();
    }

    public function testAggregateRejectsAClassStringProviderRegisteredTwice(): void
    {
        $sut = $this->makeInstance([ModuleStub1::class, ModuleStub1::class]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage(\sprintf('The provider %s is registered more than once', ModuleStub1::class));

        $sut->aggregate();
    }

    public function testAggregateRejectsTheSameProviderInstanceRegisteredTwice(): void
    {
        $provider = static fn(): array => [];
        $sut = $this->makeInstance([$provider, $provider]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('The provider Closure is registered more than once');

        $sut->aggregate();
    }

    public function testAggregateAcceptsDifferentProvidersOfTheSameType(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            static fn(): array => ['first' => true],
            static fn(): array => ['second' => true],
        ], null, $config);

        $sut->aggregate();

        $this->assertSame(['first' => true, 'second' => true], $config->toArray());
    }

    public function testPostProcessorsTransformTheMergedConfigInOrder(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([
            static fn(): array => ['value' => 'provider'],
        ], null, $config, [
            static fn(array $merged): array => ['value' => $merged['value'] . '-first', 'first' => true],
            static fn(array $merged): array => $merged + ['second' => true],
        ]);

        $sut->aggregate();

        $this->assertSame(
            ['value' => 'provider-first', 'first' => true, 'second' => true],
            $config->toArray()
        );
    }

    public function testPostProcessorCanBeAnInvokableClassString(): void
    {
        $config = new Config();
        $sut = $this->makeInstance([], null, $config, [AddProcessedFlag::class]);

        $sut->aggregate();

        $this->assertSame(AddProcessedFlag::class, $config->get('processed_by'));
    }

    public function testPostProcessedConfigIsWrittenToTheCache(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            /**
             * @var array<array-key, mixed>
             */
            public array $writtenConfig = [];

            public function read(): ?array
            {
                return null;
            }

            public function write(array $config): void
            {
                $this->writtenConfig = $config;
            }
        };

        $this->makeInstance([], $cache, null, [AddProcessedFlag::class])->aggregate();

        $this->assertSame(['processed_by' => AddProcessedFlag::class], $cache->writtenConfig);
    }

    public function testPostProcessorsAreNotAppliedToCachedConfig(): void
    {
        $cache = new class implements ProvidersCacheInterface {
            public function read(): array
            {
                return ['from_cache' => true];
            }

            public function write(array $config): void
            {
            }
        };
        $config = new Config();

        $this->makeInstance([], $cache, $config, [AddProcessedFlag::class])->aggregate();

        $this->assertSame(['from_cache' => true], $config->toArray());
    }

    public function testPostProcessorMustReturnAnArray(): void
    {
        $sut = $this->makeInstance([], null, null, [
            static fn(array $merged): string => 'invalid',
        ]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('The post processor Closure must return an array, string given');

        $sut->aggregate();
    }

    public function testPostProcessorClassStringMustBeInvokable(): void
    {
        $sut = $this->makeInstance([], null, null, [\stdClass::class]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('The post processor stdClass is not callable');

        $sut->aggregate();
    }
}
