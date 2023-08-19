<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use Brick\VarExporter\VarExporter;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Tests\Modules\ModuleStub1;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\PhpFileProvider;
use ItalyStrap\Empress\ProvidersCollection;
use ItalyStrap\Finder\FinderFactory;
use ItalyStrap\Tests\UnitTestCase;
use Prophecy\Argument;

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
            [
                new PhpFileProvider(
                    '/config/autoload/{{,*.}global,{,*.}local}.php',
                    (new FinderFactory())
                        ->make()
                        ->in(codecept_data_dir('fixtures'))
                ),
                function (): array {
                    return [
                        AurynConfig::ALIASES => [
                            self::CONFIG_KEY_2 => 'array config',
                        ],
                        AurynConfig::SHARING => [
                        ],
                    ];
                },
                function (): iterable {
                    yield [
                        AurynConfig::ALIASES => [
                            self::CONFIG_KEY_2 => 'iterable config',
                        ],
                    ];
                },
                ModuleStub1::class,
                function (): array {
                    return require \codecept_data_dir('fixtures/config/test.global.php');
                },
            ]
        );
    }

    public function testIntegration()
    {
        $sut = $this->makeInstance();

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
    }
}
