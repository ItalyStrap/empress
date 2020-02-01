<?php
declare(strict_types=1);

namespace ItalyStrap\Tests\Benchmark;

use ItalyStrap\Empress\AurynResolver;

class AurynResolverBench {

	/**
	 * @Revs(1000)
	 * @Iterations(5)
	 */
	public function benchResolver() {
		$injector = new \ItalyStrap\Empress\Injector();
		$config = \ItalyStrap\Config\ConfigFactory::make([
			AurynResolver::SHARING	=> [
				stdClass::class,
			],
		]);

		$resolver = new AurynResolver( $injector, $config );
		$resolver->resolve();
	}
}
