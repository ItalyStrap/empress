<?php
declare(strict_types=1);

namespace ItalyStrap;

use ArrayIterator;
use Auryn\Injector;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Container\Application;
use stdClass;

class Example {

	/**
	 * @var stdClass
	 */
	private $class;

	/**
	 * Example constructor.
	 * @param stdClass $class
	 */
	public function __construct( stdClass $class, ConfigInterface $config, string $param ) {
		$this->class = $class;
	}

	/**
	 * @return stdClass
	 */
	public function getClass(): stdClass {
		return $this->class;
	}
}

$config = [
	Application::ALIASES		=> [
		ConfigInterface::class	=> Config::class,
	],
	Application::SHARING		=> [
		stdClass::class,
	],
	Application::DEFINE_PARAM	=> [],
	Application::DEFINITIONS	=> [
		Example::class	=> [
			':param'	=> 42,
		]
	],
	Application::PREPARATIONS	=> [
		stdClass::class	=> function ( stdClass $class, Injector $injector ) {
			$class->param = 42;
		},
	],
	Application::DELEGATIONS	=> [
		ConfigInterface::class	=> [ ConfigFactory::class, 'make']
	],
];

$injector = new Injector();

$app = new Application( $injector, ConfigFactory::make( $config ) );
$app->resolve();

$example = $injector->make( Example::class );
$example2 = $injector->make( Example::class );

d_footer(
	$app,
	$example,
	$example2,
	$example !== $example2
);
