<?php
declare(strict_types=1);

namespace ItalyStrap\Tests;

use Codeception\Test\Unit;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\Injector;
use ItalyStrap\Empress\AurynResolver;

/**
 * Class LazyTest
 * @package ItalyStrap\Tests
 */
class LazyTest extends Unit {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	// phpcs:ignore -- Method from Codeception
    protected function _before() {
	}

	// phpcs:ignore -- Method from Codeception
    protected function _after() {
	}

	/**
	 * @test
	 */
	public function testSomeFeature() {
//    	$injector = new Injector();
//		$sut = new AurynResolver( $injector, ConfigFactory::make([
//			AurynResolver::PROXY	=> [
//				\ArrayObject::class,
//			],
//		]) );
//
//		$sut->resolve();
//
//		$class = $injector->make(\ArrayObject::class, [
//			[],
//			0,
//			\ArrayIterator::class
//		]);
//
//		codecept_debug( $class );
//
//		$class->count();
	}

	/**
	 * @test
	 */
	public function testSomeFeaturegnzdfng() {
	}
}
