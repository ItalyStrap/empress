<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests;

use Auryn\Injector;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\ProxyFactory;
use ItalyStrap\Empress\ProxyFactoryInterface;
use ItalyStrap\Finder\FinderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

abstract class UnitTestCase extends TestCase
{
    use ProphecyTrait;

    protected Injector $realInjector;

    protected function makeRealInjector(): Injector
    {
        return $this->realInjector;
    }

    protected ObjectProphecy $injector;

    protected function makeInjector(): Injector
    {
        return $this->injector->reveal();
    }

    protected ProxyFactoryInterface $realProxyFactory;

    protected ?ObjectProphecy $proxyFactory = null;

    protected function makeProxyFactory(): ProxyFactoryInterface
    {
        return $this->proxyFactory->reveal();
    }

    protected ObjectProphecy $config;

    protected function makeConfig(): ConfigInterface
    {
        return $this->config->reveal();
    }

    protected ConfigInterface $configReal;

    protected function makeConfigReal(): ConfigInterface
    {
        return $this->configReal;
    }

    protected ObjectProphecy $finder;

    protected function makeFinder(): FinderInterface
    {
        return $this->finder->reveal();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->realInjector = new Injector();
        $this->realProxyFactory = new ProxyFactory();
        $this->configReal = new Config();
        $this->injector = $this->prophesize(Injector::class);
        $this->proxyFactory = $this->prophesize(ProxyFactoryInterface::class);
        $this->config = $this->prophesize(Config::class);
        $this->finder = $this->prophesize(FinderInterface::class);
    }

    protected function tearDown(): void
    {
        unset($this->configReal);
        unset($this->realInjector);
        unset($this->realProxyFactory);
        parent::tearDown();
    }

    protected function fixturesDir(string $path = ''): string
    {
        return \dirname(__DIR__) . '/_data/fixtures/' . $path;
    }
}
