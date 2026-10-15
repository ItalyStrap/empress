<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use Auryn\Injector;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Empress\PhpFileProvider;
use ItalyStrap\Empress\ProvidersCache;
use ItalyStrap\Empress\ProvidersCollection;
use ItalyStrap\Empress\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Empress\Tests\SomeInterface;
use ItalyStrap\Finder\FinderFactory;
use ItalyStrap\Empress\Tests\Modules\ModuleStub1;
use ItalyStrap\Empress\Tests\UnitTestCase;
use Psr\Container\ContainerInterface;

final class ProvidersCollectionIntegrationTest extends UnitTestCase
{
    public const CONFIG_KEY_1 = 'Test alias should be override by local config';
    public const CONFIG_KEY_2 = 'Iterable below should override this';

    public const CONFIG_KEY_3 = 'Test test-global-php';

    private function makeInstance(): ProvidersCollection
    {
        $config = $this->makeConfigReal();
        return new ProvidersCollection(
            new Injector(),
            $config,
            new ProvidersCache($this->cachedConfigFile, 0666, true),
            [
                new PhpFileProvider(
                    '/config/autoload/{{,*.}global,{,*.}local}.php',
                    (new FinderFactory())
                        ->make()
                        ->in(codecept_data_dir('fixtures'))
                ),
                static fn(): array => [
                    AurynConfig::ALIASES => [
                        self::CONFIG_KEY_2 => 'array config',
                    ],
                    AurynConfig::SHARING => [
                    ],
                ],
                static function (): iterable {
                    yield [
                        AurynConfig::ALIASES => [
                            self::CONFIG_KEY_2 => 'iterable config',
                        ],
                    ];
                },
                static fn(): array => [
                    AurynConfig::ALIASES => [
                        'ItalyStrap\Event\GlobalDispatcherInterface' => "ItalyStrap\Event\GlobalDispatcher",
                        'talyStrap\Event\SubscriberRegisterInterface ' => "ItalyStrap\Event\SubscriberRegister",
                        'ItalyStrap\View\ViewInterface' => "ItalyStrap\View\View",
                        15 => 'value',
                    ],
                ],
                static fn(): array => [
                    AurynConfig::ALIASES => [
                        'ItalyStrap\Event\GlobalDispatcherInterface' => "ItalyStrap\Event\DifferentDispatcher",
                        'talyStrap\Event\SubscriberRegisterInterface ' => "ItalyStrap\Event\DifferentRegister",
                        'ItalyStrap\HTML\TagInterface' => "ItalyStrap\HTML\Tag",
                        15 => 'newValue',
                    ],
                ],
                ModuleStub1::class,
                [ModuleStub1::class, '__invoke'],
                new class implements ModuleInterface {
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
                },
                fn(): array => require \codecept_data_dir('fixtures/config/test.global.php'),
            ],
        );
    }

    public function testIntegration(): void
    {
        $sut = $this->makeInstance();
        $sut->aggregate();
        $config = $this->makeConfigReal();

        $this->assertSame(
            'local config',
            $config->get([
                AurynConfig::ALIASES,
                self::CONFIG_KEY_1,
            ])
        );

        $this->assertSame(
            'iterable config',
            $config->get([
                AurynConfig::ALIASES,
                self::CONFIG_KEY_2,
            ])
        );

        $this->assertSame(
            'test.global.php',
            $config->get([
                AurynConfig::ALIASES,
                self::CONFIG_KEY_3,
            ])
        );

        $this->assertFileExists($this->cachedConfigFile);
        $this->assertFileIsReadable($this->cachedConfigFile);

        $file = require $this->cachedConfigFile;
        $this->assertIsArray($file);

        $aliases = $config->get(AurynConfig::ALIASES);

        $this->assertSame('value', $aliases[0]);
        $this->assertSame('newValue', $aliases[1]);
        $this->assertCount(10, $aliases, 'Should be 10');
    }
}
