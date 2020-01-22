<?php
declare(strict_types=1);

namespace ItalyStrap\Tests;

use Auryn\ConfigException;
use Auryn\Injector;
use Codeception\Test\Unit;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Container\Application;
use ItalyStrap\Container\ApplicationInterface;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;

class AppTest extends Unit {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * @var \Prophecy\Prophecy\ObjectProphecy
	 */
	private $fake_injector;

	/**
	 * @var \Prophecy\Prophecy\ObjectProphecy
	 */
	private $config;

	// phpcs:ignore -- Method from Codeception
	protected function _before() {
		$this->fake_injector = $this->prophesize( Injector::class );
		$this->config = $this->prophesize( Config::class );
	}

	// phpcs:ignore -- Method from Codeception
	protected function _after() {
	}

	private function fakeInjector(): Injector {
		return $this->fake_injector->reveal();
	}

	private function config(): Config {
		return $this->config->reveal();
	}

	protected function getIntance( array $config = [] ) {
		$sut = new Application( $this->fakeInjector(), ConfigFactory::make( $config ) );
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
				new class {
				}
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

	/**
	 * @test
	 * @dataProvider shareProvider()
	 */
	public function itShouldThrownExceptionIfConfigHasKeyValueWith( $expected ) {

		$sut = $this->getIntance(
			[
				Application::SHARING	=> [
					'ClassName'	=> $expected,
				],
			]
		);

		$this->expectException( ConfigException::class );
		$this->expectExceptionCode( Injector::E_SHARE_ARGUMENT );
		$sut->register();
	}

	/**
	 * @test
	 */
	public function itShouldAlias() {

		$this->fake_injector
			->alias( Argument::type('string'), Argument::type('string') )
			->will( function ( $args ) {
				Assert::assertEquals( 'InterfaceName', $args[0], '' );
				Assert::assertEquals( 'ClassName', $args[1], '' );
			} );

		$sut = $this->getIntance(
			[
				Application::ALIASES	=> [
					'InterfaceName'	=> 'ClassName',
				],
			]
		);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function itShouldDefine() {

		$this->fake_injector
			->define( Argument::type('string'), Argument::type('array') )
			->will( function ( $args ) {
				Assert::assertEquals( 'ClassName', $args[0], '' );
				Assert::assertArrayHasKey( ':config', $args[1], '' );
			} );

		$sut = $this->getIntance(
			[
				Application::DEFINITIONS	=> [
					'ClassName'	=> [
						':config'	=> new class {
						}
					],
				],
			]
		);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function itShouldDefineParam() {

		$param_expected = new class {
		};

		$this->fake_injector
			->defineParam( Argument::type('string'), Argument::any() )
			->will( function ( $args ) use ( $param_expected ) {
				Assert::assertEquals( ':config', $args[0], '' );
				Assert::assertEquals( $param_expected, $args[1], '' );
			} );

		$sut = $this->getIntance(
			[
				Application::DEFINE_PARAM	=> [
					':config'	=> $param_expected,
				],
			]
		);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function itShouldDelegate() {

		$factory_delegation = function () {
			return new class {
			};
		};

		$this->fake_injector
			->delegate( Argument::type('string'), Argument::any() )
			->will( function ( $args ) use ( $factory_delegation ) {
				Assert::assertEquals( ':config', $args[0], '' );
				Assert::assertEquals( $factory_delegation, $args[1], '' );
				Assert::assertIsCallable( $args[1], '' );
			} );

		$sut = $this->getIntance(
			[
				Application::DELEGATIONS	=> [
					':config'	=> $factory_delegation,
				],
			]
		);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function itShouldPrepare() {

		$preparation_callback = function ( $class, $injector ) {
			Assert::assertEquals( 'ClassName', $class, '' );
			Assert::assertInstanceOf( Injector::class, $injector, '' );
		};

		$test = $this;

		$this->fake_injector
			->prepare( Argument::type('string'), Argument::any() )
			->will( function ( $args ) use ( $preparation_callback, $test ) {
				Assert::assertEquals( 'ClassName', $args[0], '' );
				Assert::assertEquals( $preparation_callback, $args[1], '' );
				Assert::assertIsCallable( $args[1], '' );
				\call_user_func(
					$args[1],
					'ClassName',
					$test->prophesize( Injector::class )->reveal()
				);
			} );

		$sut = $this->getIntance(
			[
				Application::PREPARATIONS	=> [
					'ClassName'	=> $preparation_callback,
				],
			]
		);

		$sut->register();
	}
}
