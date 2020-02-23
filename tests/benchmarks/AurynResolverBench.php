<?php
declare(strict_types=1);

namespace ItalyStrap\Tests\Benchmark;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynResolver;
use ItalyStrap\Empress\Injector;
use stdClass;

class AurynResolverBench {

	/**
	 * @Warmup(2)
	 * @Revs(1000)
	 * @Iterations(5)
	 */
	public function benchResolver() {
		$injector = new Injector();
		$config = ConfigFactory::make([
			AurynResolver::SHARING	=> [
				stdClass::class,
			],
		]);

		$resolver = new AurynResolver( $injector, $config );
		$resolver->resolve();

		$class = $injector->make(stdClass::class);
	}

	/**
	 * @Warmup(2)
	 * @Revs(1000)
	 * @Iterations(5)
	 */
	public function benchResolverP() {
		$injector = new Injector();
		$injector->share(stdClass::class);
		$class = $injector->make(stdClass::class);
	}
}
