<?php
declare(strict_types=1);

namespace ItalyStrap\Tests\Benchmark;

use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\Injector;
use stdClass;

class AurynConfigBench {

	/**
	 * @Warmup(2)
	 * @Revs(1000)
	 * @Iterations(5)
	 */
	public function benchResolver() {
		$injector = new Injector();
		$config = ConfigFactory::make([
			AurynConfig::SHARING	=> [
				stdClass::class,
			],
		]);

		$resolver = new AurynConfig( $injector, $config );
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
