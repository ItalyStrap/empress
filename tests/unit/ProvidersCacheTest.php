<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Config\Config;
use ItalyStrap\Empress\ProvidersCache;
use ItalyStrap\Empress\ProvidersCacheInterface;
use ItalyStrap\Empress\Tests\UnitTestCase;

final class ProvidersCacheTest extends UnitTestCase
{
    /**
     * @var array<int, string>
     */
    private array $pathsToRemove = [];

    // phpcs:ignore -- Method from Codeception
    protected function _after(): void {
        foreach (\array_reverse($this->pathsToRemove) as $path) {
            if (\is_file($path)) {
                \unlink($path);
                continue;
            }

            if (\is_dir($path)) {
                \rmdir($path);
            }
        }

        parent::_after();
    }

    public function testReadReturnsFalseWhenNoCachePathIsConfigured(): void
    {
        $sut = new ProvidersCache();

        $this->assertFalse($sut->read(new Config()));
    }

    public function testReadReturnsFalseWhenCachePathDoesNotExist(): void
    {
        $sut = new ProvidersCache($this->cacheFile('missing-cache.php'));

        $this->assertFalse($sut->read(new Config()));
    }

    public function testReadReturnsFalseWhenCachePathIsNotAFile(): void
    {
        $directory = $this->cacheDirectory('cache-directory');
        $sut = new ProvidersCache($directory);

        $this->assertFalse($sut->read(new Config()));
    }

    public function testReadMergesArrayReturnedByCacheFile(): void
    {
        $file = $this->writeCacheFile('read-cache.php', <<<'PHP'
<?php

return [
    'key' => 'value',
    'nested' => [
        'key' => 'nested-value',
    ],
];
PHP);
        $config = new Config();
        $sut = new ProvidersCache($file);

        $this->assertTrue($sut->read($config));
        $this->assertSame('value', $config->get('key'));
        $this->assertSame('nested-value', $config->get('nested.key'));
    }

    public function testReadUsesConfigCachePathBeforeConstructorPath(): void
    {
        $constructorFile = $this->writeCacheFile('constructor-cache.php', <<<'PHP'
<?php

return [
    'source' => 'constructor',
];
PHP);
        $configFile = $this->writeCacheFile('config-cache-override.php', <<<'PHP'
<?php

return [
    'source' => 'config',
];
PHP);
        $config = new Config([
            ProvidersCacheInterface::CACHE_PATH => $configFile,
        ]);
        $sut = new ProvidersCache($constructorFile);

        $this->assertTrue($sut->read($config));
        $this->assertSame('config', $config->get('source'));
    }

    public function testReadThrowsWhenCacheFileDoesNotReturnArray(): void
    {
        $file = $this->writeCacheFile('non-array-cache.php', <<<'PHP'
<?php

return 'invalid';
PHP);
        $sut = new ProvidersCache($file);

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Configuration cache must return an array');

        $sut->read(new Config());
    }

    public function testReadWrapsErrorsThrownByCacheFile(): void
    {
        $file = $this->writeCacheFile('throwing-cache.php', <<<'PHP'
<?php

throw new RuntimeException('Broken cache file');
PHP);
        $sut = new ProvidersCache($file);

        try {
            $sut->read(new Config());
            $this->fail('Expected cache read failure');
        } catch (\ErrorException $exception) {
            $this->assertSame('Configuration cache cannot be read', $exception->getMessage());
            $this->assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
            $this->assertSame('Broken cache file', $exception->getPrevious()->getMessage());
        }
    }

    public function testWriteReturnsWhenNoCachePathIsConfigured(): void
    {
        $sut = new ProvidersCache();

        $sut->write(new Config([
            'key' => 'value',
        ]));

        $this->assertTrue(true);
    }

    public function testWriteCreatesReadableCacheFile(): void
    {
        $file = $this->cacheFile('written-cache.php');
        $this->pathsToRemove[] = $file;
        $config = new Config([
            ProvidersCacheInterface::CACHE_PATH => $file,
            'key' => 'value',
        ]);
        $sut = new ProvidersCache();

        $sut->write($config);

        $this->assertFileExists($file);
        $this->assertFileIsReadable($file);
        $cachedConfig = require $file;

        $this->assertIsArray($cachedConfig);
        $this->assertSame('value', $cachedConfig['key']);
    }

    public function testWriteThrowsWhenCacheFileCannotBeWritten(): void
    {
        $config = new Config([
            ProvidersCacheInterface::CACHE_PATH => $this->cacheFile('missing-directory/cache.php'),
        ]);
        $sut = new ProvidersCache();

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Configuration cache cannot be written');

        $sut->write($config);
    }

    private function cacheFile(string $name): string
    {
        return \codecept_output_dir($name);
    }

    private function cacheDirectory(string $name): string
    {
        $directory = \codecept_output_dir($name);

        if (!\is_dir($directory)) {
            \mkdir($directory);
        }

        $this->pathsToRemove[] = $directory;
        return $directory;
    }

    private function writeCacheFile(string $name, string $contents): string
    {
        $file = $this->cacheFile($name);
        \file_put_contents($file, $contents);
        $this->pathsToRemove[] = $file;

        return $file;
    }
}
