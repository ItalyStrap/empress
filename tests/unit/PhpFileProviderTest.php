<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Unit;

use ItalyStrap\Empress\PhpFileProvider;
use ItalyStrap\Tests\UnitTestCase;
use PHPUnit\Framework\Assert;

class PhpFileProviderTest extends UnitTestCase
{
    protected function makeInstance(): PhpFileProvider
    {
        return new PhpFileProvider('pattern', $this->makeFinder());
    }

    public function testShouldBeInvokable()
    {
        $file = \codecept_data_dir('fixtures/config/autoload/config.global.php');

        $this->finder->names(['pattern'])->will(function ($args): void {
            Assert::assertSame('pattern', $args[0][0], 'Should be the same pattern');
        });

        $this->finder->getIterator()->willReturn(new \ArrayIterator([
            $file,
        ]));

        $expected = require $file;

        $sut = $this->makeInstance();
        foreach ($sut() as $actual) {
            $this->assertEquals($expected, $actual, 'Should be expected file');
            break; // Only do on first iteration
        }
    }
}
