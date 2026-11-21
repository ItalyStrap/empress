<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests\Unit;

use ItalyStrap\Empress\NativeProxyFactory;
use ItalyStrap\Empress\Tests\FinalService;
use ItalyStrap\Empress\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

final class NativeProxyFactoryTest extends UnitTestCase
{
    private int $calls = 0;

    private function makeProxy(): FinalService
    {
        /** @var FinalService */
        return (new NativeProxyFactory())(FinalService::class, function (): FinalService {
            $this->calls++;
            return new FinalService('real');
        });
    }

    #[RequiresPhp('< 8.4')]
    public function testThrowsOnPhpVersionsWithoutNativeLazyObjects(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('PHP 8.4');

        $this->makeProxy();
    }

    #[RequiresPhp('>= 8.4')]
    public function testProxyOfAFinalClassIsCreatedWithoutCallingTheCallback(): void
    {
        $proxy = $this->makeProxy();

        $this->assertInstanceOf(FinalService::class, $proxy);
        $this->assertTrue((new \ReflectionClass(FinalService::class))->isUninitializedLazyObject($proxy));
        $this->assertSame(0, $this->calls);
    }

    #[RequiresPhp('>= 8.4')]
    public function testFirstUseCallsTheCallbackOnceAndDelegatesToTheRealInstance(): void
    {
        $proxy = $this->makeProxy();

        $this->assertSame('real', $proxy->value());
        $this->assertSame('real', $proxy->value());
        $this->assertSame(1, $this->calls);
    }
}
