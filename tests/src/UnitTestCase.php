<?php

declare(strict_types=1);

namespace ItalyStrap\Tests;

use Codeception\Test\Unit;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Finder\FinderInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use UnitTester;

class UnitTestCase extends Unit
{
    use ProphecyTrait;

    protected UnitTester $tester;

    protected ObjectProphecy $injector;

    protected function makeInjector(): Injector
    {
        return $this->injector->reveal();
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
        $this->prophet = new Prophet();
        $this->injector = $this->prophet->prophesize(Injector::class);
        $this->configReal = new Config();
        $this->config = $this->prophet->prophesize(Config::class);
        $this->finder = $this->prophet->prophesize(FinderInterface::class);

        $this->cachedConfigFile = codecept_output_dir('config-cache.php');
    }

    // phpcs:ignore -- Method from Codeception
    protected function _after(): void {
        $this->configReal = clone $this->configReal;
        $this->prophet->checkPredictions();
        unset($this->config);
        \file_exists($this->cachedConfigFile) and unlink($this->cachedConfigFile);
    }
}
