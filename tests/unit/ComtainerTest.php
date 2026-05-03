<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Empress\Container;
use ItalyStrap\Empress\Tests\ConcreteNeedsSomeInterface;
use ItalyStrap\Empress\Tests\SomeConcrete;
use ItalyStrap\Empress\Tests\UnitTestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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

    public function testGetNotFoundExceptionIsAlsoContainerException(): void
    {
        $sut = $this->makeInstance();

        try {
            $sut->get('non.existing.service');
            $this->fail('Expected a not found exception');
        } catch (NotFoundExceptionInterface $exception) {
            $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
            $this->assertSame("Service 'non.existing.service' not found", $exception->getMessage());
        }
    }

    public function testGetReturnsInstanceForExistingClass(): void
    {
        $sut = $this->makeInstance();
        $instance = $sut->get(SomeConcrete::class);
        $this->assertInstanceOf(SomeConcrete::class, $instance);
    }

    public function testGetWrapsAurynResolutionErrorsInContainerException(): void
    {
        $sut = $this->makeInstance();

        try {
            $sut->get(ConcreteNeedsSomeInterface::class);
            $this->fail('Expected a container exception');
        } catch (ContainerExceptionInterface $exception) {
            $this->assertNotInstanceOf(NotFoundExceptionInterface::class, $exception);
            $this->assertStringContainsString(
                ConcreteNeedsSomeInterface::class,
                $exception->getMessage()
            );
            $this->assertNotNull($exception->getPrevious());
        }
    }
}
