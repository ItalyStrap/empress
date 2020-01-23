<?php
declare(strict_types=1);

namespace ItalyStrap\Container;

use Auryn\ConfigException;
use Auryn\InjectionException;
use Auryn\Injector;
use ItalyStrap\Config\ConfigInterface as Config;

class Application implements ApplicationInterface {

	const SHARING = 'sharing';
	const ALIASES = 'aliases';
	const DEFINITIONS = 'definitions';
	const DEFINE_PARAM = 'define_param';
	const DELEGATIONS = 'delegations';
	const PREPARATIONS = 'preparations';

	private const DEFAULT = [
		self::SHARING		=> 'share',
		self::ALIASES		=> 'alias',
		self::DEFINITIONS	=> 'define',
		self::DEFINE_PARAM	=> 'defineParam',
		self::DELEGATIONS	=> 'delegate',
		self::PREPARATIONS	=> 'prepare',
	];

	/**
	 * @var Injector
	 */
	private $injector;
	/**
	 * @var Config
	 */
	private $dependencies;

	/**
	 * @var array<Extension>
	 */
	private $extensions = [];

	/**
	 * Application constructor.
	 * @param Config $dependencies
	 * @param Injector $injector
	 */
	public function __construct( Injector $injector, Config $dependencies ) {
		$this->injector = $injector;
		$this->dependencies = $dependencies;
	}

	/**
	 * @inheritDoc
	 */
	public function resolve(): void {

		/**
		 * @var string $key
		 * @var callable $method
		 */
		foreach ( self::DEFAULT as $key => $method ) {
			$this->walk( $key, [ $this, $method ] );
		}

		/** @var Extension $extension */
		foreach ( $this->extensions as $extension ) {
			$extension->execute( $this );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function extend( Extension ...$extensions ): void {
		foreach ( $extensions as $extension ) {
			$this->extensions[ $extension->name() ] = $extension;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function walk( string $key, callable $callback ): void {
		$value = $this->dependencies->get( $key, [] );
		\array_walk( $value, $callback, $this->injector );
	}

	/**
	 * @param mixed $nameOrInstance
	 * @param int $index
	 * @throws ConfigException
	 */
	protected function share( $nameOrInstance, $index ): void {

		if ( ! \is_int( $index ) ) {
			throw new ConfigException(
				sprintf(
					'%s::share() config does not have $key => $value pair, only $value',
					__CLASS__
				),
				Injector::E_SHARE_ARGUMENT
			);
		}

		$this->injector->share( $nameOrInstance );
	}

	/**
	 * @param string $implementation
	 * @param string $interface
	 * @throws ConfigException
	 */
	protected function alias( string $implementation, string $interface ): void {
		$this->injector->alias( $interface, $implementation );
	}

	/**
	 * @param array $class_args
	 * @param string $class_name
	 */
	protected function define( array $class_args, string $class_name ): void {
		$this->injector->define( $class_name, $class_args );
	}

	/**
	 * @param mixed $param_args
	 * @param string $param_name
	 */
	protected function defineParam( $param_args, string $param_name ): void {
		$this->injector->defineParam( $param_name, $param_args );
	}

	/**
	 * @param string $callableOrMethodStr
	 * @param string $name
	 * @throws ConfigException
	 */
	protected function delegate( $callableOrMethodStr, string $name ) {
		$this->injector->delegate( $name, $callableOrMethodStr );
	}

	/**
	 * @param mixed $callableOrMethodStr
	 * @param string $name
	 * @throws InjectionException
	 */
	protected function prepare( $callableOrMethodStr, string $name ) {
		$this->injector->prepare( $name, $callableOrMethodStr );
	}
}
