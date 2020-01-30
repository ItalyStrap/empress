<?php
declare(strict_types=1);

namespace ItalyStrap\Tests;

use Auryn\ConfigException;
use Codeception\Test\Unit;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Empress\AurynResolver;
use ItalyStrap\Empress\AurynResolverInterface;
use ItalyStrap\Empress\Extension;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;

/**
 * Class AppTest
 * @package ItalyStrap\Tests
 */
class AurynResolverTest extends Unit {

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

	protected function getIntance( array $config = [] ) {
		$sut = new AurynResolver( $this->fakeInjector(), ConfigFactory::make( $config ) );
		$this->assertInstanceOf( AurynResolverInterface::class, $sut, '' );
		$this->assertInstanceOf( AurynResolver::class, $sut, '' );
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
				AurynResolver::SHARING	=> [
					$expected,
				],
			]
		);

		$sut->resolve();
	}

	/**
	 * @test
	 * @dataProvider shareProvider()
	 */
	public function itShouldThrownExceptionIfConfigHasKeyValueWith( $expected ) {

		$sut = $this->getIntance(
			[
				AurynResolver::SHARING	=> [
					'ClassName'	=> $expected,
				],
			]
		);

		$this->expectException( ConfigException::class );
		$this->expectExceptionCode( Injector::E_SHARE_ARGUMENT );
		$sut->resolve();
	}

	/**
	 * @test
	 */
	public function itShouldProxy() {

		$expected = 'SomeClassProxies';

		$this->fake_injector->proxy(
			Argument::type( 'string' ),
			Argument::any()
		)->will( function ( $args ) use ( $expected ) {
			Assert::assertEquals( $expected, $args[0], '' );
		} );

		$sut = $this->getIntance(
			[
				AurynResolver::PROXY	=> [
					$expected,
				],
			]
		);

		$sut->resolve();
	}

	/**
	 * @test
	 */
	public function itShouldThrownExceptionIfProxyHasStringInKey() {

		$expected = 'SomeClassProxies';

		$sut = $this->getIntance(
			[
				AurynResolver::PROXY	=> [
					'ClassName'	=> $expected,
				],
			]
		);

		$this->expectException( ConfigException::class );
		$this->expectExceptionCode( Injector::E_PROXY_ARGUMENT );
		$sut->resolve();
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
				AurynResolver::ALIASES	=> [
					'InterfaceName'	=> 'ClassName',
				],
			]
		);

		$sut->resolve();
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
				AurynResolver::DEFINITIONS	=> [
					'ClassName'	=> [
						':config'	=> new class {
						}
					],
				],
			]
		);

		$sut->resolve();
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
				AurynResolver::DEFINE_PARAM	=> [
					':config'	=> $param_expected,
				],
			]
		);

		$sut->resolve();
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
				AurynResolver::DELEGATIONS	=> [
					':config'	=> $factory_delegation,
				],
			]
		);

		$sut->resolve();
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
				AurynResolver::PREPARATIONS	=> [
					'ClassName'	=> $preparation_callback,
				],
			]
		);

		$sut->resolve();
	}

	/**
	 * @test
	 */
	public function itShouldWalk() {
		$sut = $this->getIntance(
			[
				'Test'	=> [
					'Key'	=> 'ClassName',
				],
			]
		);

		$sut->walk( 'Test', function ( string $value, $key ) {
			Assert::assertStringContainsString( $value, 'ClassName', '' );
			Assert::assertStringContainsString( $key, 'Key', '' );
		} );
	}

	/**
	 * @test
	 */
	public function itShouldExtendFakeClass() {

		$this->fake_injector->share( Argument::type( 'string' ), Argument::any() )
			->will(function ($args) {
				Assert::assertStringContainsString( 'ClassName', $args[0], '' );
			});

		$this->fake_injector->make( Argument::type( 'string' ), Argument::type('array') )
			->will(function ($args) {
				Assert::assertStringContainsString( 'ClassName', $args[0], '' );
			});

		$sut = $this->getIntance(
			[
				'subscribers'	=> [
					'ClassName',
					'option-name'	=> 'ClassName',
				],
			]
		);

		$extension = $this->prophesize( Extension::class );

		$extension->name()->willReturn( 'ExtensionName' );

		$extension->execute( Argument::exact( $sut ) )->will(function ( $args ) {
		});

		$sut->extend( $extension->reveal() );

		$sut->resolve();
	}

	/**
	 * @test
	 */
	public function itShouldExtendRealClass() {

		$this->fake_injector->share( Argument::type( 'string' ), Argument::any() )
			->will(function ($args) {
				Assert::assertStringContainsString( 'ClassName', $args[0], '' );
			});

		$this->fake_injector->make( Argument::type( 'string' ), Argument::type('array') )
			->will(function ($args) {
				Assert::assertStringContainsString( 'ClassName', $args[0], '' );
			});

		$sut = $this->getIntance(
			[
				'subscribers'	=> [
					'ClassName',
					'option-name'	=> 'ClassName',
				],
			]
		);

		$sut->extend( new class implements Extension {

			/** @var string */
			const SUBSCRIBERS = 'subscribers';

			public function name(): string {
				return (string) self::SUBSCRIBERS;
			}

			public function execute( AurynResolverInterface $application ) {
				$application->walk( (string) self::SUBSCRIBERS, [ $this, 'method' ] );
			}

			public function method( string $class, $index_or_optionName, Injector $injector ) {
				Assert::assertStringContainsString( $class, 'ClassName', '' );
				$injector->share( $class );
				$injector->make( $class, [] );

//				if ( empty( $config->get( $index_or_optionName, '' ) ) ) {
//					return;
//				}
//
//				$event_manager = $injector->make( EventManager::class );
//				$event_manager->add_subscriber( $injector->share( $class )->make( $class ) );
			}
		} );

		$sut->resolve();
	}
}
