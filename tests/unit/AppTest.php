<?php
declare(strict_types=1);

namespace ItalyStrap\Tests;

use Auryn\Injector;
use Codeception\Test\Unit;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Container\Application;
use ItalyStrap\Container\ApplicationInterface;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;

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

	protected function getIntance( array $config = [] ) {
		$sut = new Application( ConfigFactory::make( $config ), $this->fakeInjector() );
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

	public function shareProvider() {
		return [
			'ClassName'		=> [
				'SomeClassName'
			],
			'ClassInstance'	=> [
				new class {}
			],
		];
    }

	/**
	 * @test
	 * @dataProvider shareProvider()
	 */
	public function itShouldShare( $expected ) {

		$this->fake_injector->share( Argument::any() )->will( function ( $args ) use ( $expected ) {
			Assert::assertEquals( $expected, $args[0], '' );
		} );

		$sut = $this->getIntance(
			[
				Application::SHARING	=> [
					$expected,
				],
			]
		);

		$sut->register();
    }
}
