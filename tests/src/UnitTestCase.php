<?php

declare(strict_types=1);

namespace ItalyStrap\Tests;

use Codeception\Test\Unit;
use ItalyStrap\Empress\Injector;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use UnitTester;

class UnitTestCase extends Unit
{
    protected UnitTester $tester;
    protected ObjectProphecy $injector;
    protected Prophet $prophet;

    protected function makeInjector(): Injector
    {
        return $this->injector->reveal();
    }

    // phpcs:ignore -- Method from Codeception
    protected function _before(): void {
        $this->prophet = new Prophet();
        $this->injector = $this->prophet->prophesize(Injector::class);
    }

    // phpcs:ignore -- Method from Codeception
    protected function _after(): void {
        $this->prophet->checkPredictions();
    }
}
