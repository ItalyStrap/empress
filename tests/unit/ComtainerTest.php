<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Empress\Container;
use ItalyStrap\Empress\Tests\SomeConcrete;
use ItalyStrap\Empress\Tests\UnitTestCase;
use Psr\Container\NotFoundExceptionInterface;
use Prophecy\Argument;

final class ComtainerTest extends UnitTestCase
{
    private function makeInstance(): Container
    {
        return new Container($this->makeRealInjector());
    }

    public function testHasReturnsTrueForExistingClass(): void
    {
        $sut = $this->makeInstance();
        $this->assertTrue($sut->has(SomeConcrete::class));
    }

    public function testHasReturnsFalseForNonExistingService(): void
    {
        $sut = $this->makeInstance();
        $this->assertFalse($sut->has('non.existing.service'));
    }

    public function testGetThrowsNotFoundExceptionForNonExistingService(): void
    {
        $sut = $this->makeInstance();

        $this->expectException(NotFoundExceptionInterface::class);

        $sut->get('non.existing.service');
    }

    public function testGetReturnsInstanceForExistingClass(): void
    {
        $sut = $this->makeInstance();
        $instance = $sut->get(SomeConcrete::class);
        $this->assertInstanceOf(SomeConcrete::class, $instance);
    }
}
