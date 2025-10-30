<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use Auryn\Injector;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\PhpFileProvider;
use ItalyStrap\Empress\ProvidersCollection;
use ItalyStrap\Finder\FinderFactory;
use ItalyStrap\Empress\Tests\Modules\ModuleStub1;
use ItalyStrap\Empress\Tests\UnitTestCase;

class ProvidersCollectionIntegrationTest extends UnitTestCase
{
    public const CONFIG_KEY_1 = 'Test alias should be override by local config';
    public const CONFIG_KEY_2 = 'Iterable below should override this';

    public const CONFIG_KEY_3 = 'Test test-global-php';

    private function makeInstance(): ProvidersCollection
    {
        return new ProvidersCollection(
            new Injector(),
            $this->makeConfigReal(),
            null,
            [
                new PhpFileProvider(
                    '/config/autoload/{{,*.}global,{,*.}local}.php',
                    (new FinderFactory())
                        ->make()
                        ->in(codecept_data_dir('fixtures'))
                ),
                fn(): array => [
                    AurynConfig::ALIASES => [
                        self::CONFIG_KEY_2 => 'array config',
                    ],
                    AurynConfig::SHARING => [
                    ],
                ],
                function (): iterable {
                    yield [
                        AurynConfig::ALIASES => [
                            self::CONFIG_KEY_2 => 'iterable config',
                        ],
                    ];
                },
                fn(): array => [
                    AurynConfig::ALIASES => [
                        'ItalyStrap\Event\GlobalDispatcherInterface' => "ItalyStrap\Event\GlobalDispatcher",
                        'talyStrap\Event\SubscriberRegisterInterface ' => "ItalyStrap\Event\SubscriberRegister",
                        'ItalyStrap\View\ViewInterface' => "ItalyStrap\View\View",
                        15 => 'value',
                    ],
                ],
                fn(): array => [
                    AurynConfig::ALIASES => [
                        'ItalyStrap\Event\GlobalDispatcherInterface' => "ItalyStrap\Event\DifferentDispatcher",
                        'talyStrap\Event\SubscriberRegisterInterface ' => "ItalyStrap\Event\DifferentRegister",
                        'ItalyStrap\HTML\TagInterface' => "ItalyStrap\HTML\Tag",
                        15 => 'newValue',
                    ],
                ],
                ModuleStub1::class,
                [ModuleStub1::class, '__invoke'],
                fn(): array => require \codecept_data_dir('fixtures/config/test.global.php'),
                fn(): array => [
                    'config_cache_enabled' => true,
                    'cache_config_path' => $this->cachedConfigFile,
                ],
            ],
        );
    }

    public function testIntegration(): void
    {
        $sut = $this->makeInstance();
        $sut->build();

        $this->assertSame(
            'local config',
            $sut->collection()->get(\implode('.', [
                AurynConfig::ALIASES,
                self::CONFIG_KEY_1,
            ]))
        );

        $this->assertSame(
            'iterable config',
            $sut->collection()->get(\implode('.', [
                AurynConfig::ALIASES,
                self::CONFIG_KEY_2,
            ]))
        );

        $this->assertSame(
            'test.global.php',
            $sut->collection()->get(\implode('.', [
                AurynConfig::ALIASES,
                self::CONFIG_KEY_3,
            ]))
        );

        $this->assertFileExists($this->cachedConfigFile);
        $this->assertFileIsReadable($this->cachedConfigFile);

        $file = require $this->cachedConfigFile;
        $this->assertIsArray($file);

        /**
         * \array_merge() will append the value if the kew is numeric
         */
        $this->assertCount(10, $sut->collection()->get(AurynConfig::ALIASES), 'Should be 10');
    }
}
