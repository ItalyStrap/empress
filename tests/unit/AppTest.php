<?php
declare(strict_types=1);

namespace ItalyStrap\Tests;

use Auryn\Injector;
use Codeception\Test\Unit;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Container\Application;
use ItalyStrap\Container\ApplicationInterface;

class AppTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

	/**
	 * @var \Prophecy\Prophecy\ObjectProphecy
	 */
	private $fake_injector;

	protected function _before()
    {
    	$this->fake_injector = $this->prophesize( Injector::class );
    }

    protected function _after()
    {
    }

	private function fakeInjector(): Injector {
		return $this->fake_injector->reveal();
    }

	protected function getIntance() {
		$sut = new Application( ConfigFactory::make([]), $this->fakeInjector() );
		$this->assertInstanceOf( ApplicationInterface::class, $sut, '' );
		$this->assertInstanceOf( Application::class, $sut, '' );
		return $sut;
    }

	/**
	 * @test
	 */
	public function itShouldBeInstantiable() {
		$sut = $this->getIntance();
    }

	/**
	 * @test
	 */
	public function itShouldBeRegistrable() {
		$sut = $this->getIntance();
		$sut->register();
    }
}
