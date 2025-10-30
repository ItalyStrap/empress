<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests;

use Auryn\Injector;
use Codeception\Test\Unit;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\ProxyFactory;
use ItalyStrap\Empress\ProxyFactoryInterface;
use ItalyStrap\Finder\FinderInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class UnitTestCase extends Unit
{
    use ProphecyTrait;

    protected UnitTester $tester;

    protected Injector $realInjector;

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

    protected string $cachedConfigFile;

    // phpcs:ignore -- Method from Codeception
    protected function _before(): void {
        $this->realInjector = new Injector();
        $this->realProxyFactory = new ProxyFactory();
        $this->configReal = new Config();
        $this->injector = $this->prophesize(Injector::class);
        $this->proxyFactory = $this->prophesize(ProxyFactoryInterface::class);
        $this->config = $this->prophesize(Config::class);
        $this->finder = $this->prophesize(FinderInterface::class);

        $this->cachedConfigFile = codecept_output_dir('config-cache.php');
    }

    // phpcs:ignore -- Method from Codeception
    protected function _after(): void {
        $this->prophet->checkPredictions();
        unset($this->configReal);
        unset($this->realInjector);
        unset($this->realProxyFactory);
        \file_exists($this->cachedConfigFile) and unlink($this->cachedConfigFile);
    }
}
