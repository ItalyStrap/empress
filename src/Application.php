<?php
declare(strict_types=1);

namespace ItalyStrap\Container;


use Auryn\Injector;
use ItalyStrap\Config\ConfigInterface as Config;

class Application implements ApplicationInterface
{
	const SHARING = 'sharing';
	const ALIASES = 'aliases';
	const DEFINITIONS = 'definitions';
	const DEFINE_PARAM = 'define_param';
	const DELEGATIONS = 'delegations';
	const PREPARATIONS = 'preparations';
	const SUBSCRIBERS = 'subscribers';

	/**
	 * @var Injector
	 */
	private $injector;
	/**
	 * @var Config
	 */
	private $dependencies;

	/**
	 * Application constructor.
	 * @param Config $dependencies
	 * @param Injector $injector
	 */
	public function __construct( Config $dependencies, Injector $injector ) {
		$this->injector = $injector;
		$this->dependencies = $dependencies;
	}

	public function register() {

		$default_key = [
			self::SHARING		=> 'share',
			self::ALIASES		=> 'alias',
			self::DEFINITIONS	=> 'define',
			self::DEFINE_PARAM	=> 'defineParam',
			self::DELEGATIONS	=> 'delegate',
			self::PREPARATIONS	=> 'prepare',
			self::SUBSCRIBERS	=> 'subscribe',
		];

		foreach ( $default_key as $key => $method ) {
			$value = $this->dependencies->get( $key, [] );
			\array_walk( $value, [ $this, $method ] );
		}
	}

	/**
	 * @param $class
	 * @param $interface
	 * @throws \Auryn\ConfigException
	 */
	protected function share( $nameOrInstance, $index ) {

		if ( ! \is_int( $index ) ) {
			throw new \RuntimeException( 'Sharing config does not have $key => $value pair, only $value' );
		}

		$this->injector->share( $nameOrInstance );
	}

	/**
	 * @param $implementation
	 * @param $interface
	 * @throws \Auryn\ConfigException
	 */
	protected function alias( $implementation, $interface ) {
		$this->injector->alias( $interface, $implementation );
	}

	/**
	 * @param $class_args
	 * @param $class_name
	 */
	protected function define( $class_args, $class_name ) {
		$this->injector->define( $class_name, $class_args );
	}

	/**
	 * @param $param_args
	 * @param $param_name
	 */
	protected function defineParam( $param_args, $param_name ) {
		$this->injector->defineParam( $param_name, $param_args );
	}

	/**
	 * @param $callableOrMethodStr
	 * @param $name
	 * @throws \Auryn\ConfigException
	 */
	protected function delegate( $callableOrMethodStr, $name ) {
		$this->injector->delegate( $name, $callableOrMethodStr );
	}

	/**
	 * @param $callableOrMethodStr
	 * @param $name
	 * @throws \Auryn\InjectionException
	 */
	protected function prepare( $callableOrMethodStr, $name ) {
		$this->injector->prepare( $name, $callableOrMethodStr );
	}

	/**
	 * @param $concrete
	 * @param $option_name
	 */
	protected function subscribe( $concrete, $option_name ) {

//		if ( is_string( $option_name ) && $config->has( $option_name ) && empty( $config->get( $option_name ) ) ) {
//			continue;
//		}

//		$event_manager->add_subscriber( $this->injector->share( $concrete )->make( $concrete ) );
	}
}
