<?php
declare(strict_types=1);

namespace ItalyStrap;

use ArrayIterator;
use Auryn\Injector;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\AurynResolver;
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

	public function execute( string $text ) {
		return $text;
	}
}

$config = [
	AurynResolver::ALIASES		=> [
		ConfigInterface::class	=> Config::class,
	],
	AurynResolver::SHARING		=> [
		stdClass::class,
	],
	AurynResolver::DEFINE_PARAM	=> [
		'text'	=> 'Some Text'
	],
	AurynResolver::DEFINITIONS	=> [
		Example::class	=> [
			':param'	=> 42,
		]
	],
	AurynResolver::PREPARATIONS	=> [
		stdClass::class	=> function ( stdClass $class, Injector $injector ) {
			$class->param = 42;
		},
	],
	AurynResolver::DELEGATIONS	=> [
		ConfigInterface::class	=> [ ConfigFactory::class, 'make']
	],
];

$injector = new Injector();

$app = new AurynResolver( $injector, ConfigFactory::make( $config ) );
$app->resolve();

$example = $injector->make( Example::class );
$example2 = $injector->make( Example::class );

$result = $injector->execute( [ $example, 'execute' ] );

d_footer(
	$app,
	$example,
	$example2,
	$example !== $example2,
	$result
);
