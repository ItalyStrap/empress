<?php
declare(strict_types=1);

namespace ItalyStrap;

use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\AurynResolver;
use ItalyStrap\Empress\Injector;
use stdClass;

class Example {

	/**
	 * @var stdClass
	 */
	private $class;
	/**
	 * @var ConfigInterface
	 */
	private $config;
	/**
	 * @var string
	 */
	private $param;

	/**
	 * Example constructor.
	 * @param stdClass $class
	 * @param ConfigInterface $config
	 * @param string $param
	 */
	public function __construct( stdClass $class, ConfigInterface $config, string $param ) {
		$this->class = $class;
		$this->config = $config;
		$this->param = $param;
	}

	/**
	 * @return ConfigInterface
	 */
	public function getConfig(): ConfigInterface {
		return $this->config;
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

/**
 * The better way to add keys to the array configuration is to use the
 * AurynResolver::<CONSTANT KEYS>
 *
 * Keys available are:
 *
 * AurynResolver::PROXY = 'proxies';
 * AurynResolver::SHARING = 'sharing';
 * AurynResolver::ALIASES = 'aliases';
 * AurynResolver::DEFINITIONS = 'definitions';
 * AurynResolver::DEFINE_PARAM = 'define_param';
 * AurynResolver::DELEGATIONS = 'delegations';
 * AurynResolver::PREPARATIONS = 'preparations';
 */

$config = [

	/**
	 * Example:
	 * class MyCLass( ConfigInterface $config ) {}
	 * You alias a `ConfigInterface::class` to `Config::class`
	 * $injector->make(MyCLass::class); will be injected with a Config object
	 */
	AurynResolver::ALIASES		=> [
		ConfigInterface::class	=> Config::class,
	],

	/**
	 * Example:
	 * class MyCLass( ConfigInterface $global_config, \stdClass $class ) {}
	 * class MyOtherCLass( ConfigInterface $global_config, \stdClass $class ) {}
	 * A new Config instance will be shared, think of it like a singleton but more better and OOP oriented
	 * The same instance of Config will be injected to MyCLass and MyOtherCLass
	 * $injector->make(MyCLass::class); // Will have $global_config
	 * $injector->make(MyOtherCLass::class); // Will have $global_config
	 * @see [Instance Sharing](https://github.com/rdlowrey/auryn#instance-sharing)
	 */
	AurynResolver::SHARING		=> [
		stdClass::class,
		ConfigInterface::class,
	],

	/**
	 * This is the new feature for the Auryn\Injector implemented in the bridge adapter
	 * You usually need a lazy value holder in cases where the following applies:
	 *  * your object takes a lot of time and memory to be initialized (with all dependencies)
	 *  * your object is not always used, and the instantiation overhead is avoidable
	 * Example:
	 * HeavyComplexObject( ...HeavyDependency ); // Declared somewhere
	 * $object = $injector->make(HeavyComplexObject::class);
	 * add_{filter|action}( 'event_name', [ $object, 'doSomeStuff' ] );
	 *
	 * You can proxies the `HeavyComplexObject::class` dependency
	 * $injector->proxy(HeavyDependency::class);
	 * Or the `HeavyComplexObject::class` directly the business logic is up to you
	 * $injector->proxy(HeavyComplexObject::class);
	 *
	 * Now you call
	 * $object = $injector->make(HeavyComplexObject::class);
	 * With proxy will be:
	 * add_{filter|action}( 'event_name', [ $object, 'doSomeStuff' ] );
	 * @see [Lazy Loading Value Holder Proxy](https://github.com/Ocramius/ProxyManager/blob/master/docs/lazy-loading-value-holder.md)
	 */
	AurynResolver::PROXY		=> [
		Config::class,
	],

	/**
	 * Define global parameter
	 * class SomeCLass( $text ) {}
	 * class SomeOtherCLass( $text ) {}
	 * $injector->make(SomeCLass::class);
	 * $injector->make(SomeOtherCLass::class);
	 * Now the `$text` will be decorated with 'Some Text'
	 * @see [Global Parameter Definitions](https://github.com/rdlowrey/auryn#global-parameter-definitions)
	 */
	AurynResolver::DEFINE_PARAM	=> [
		'text'	=> 'Some Text'
	],

	/**
	 * Definition for class specific
	 * Example:
	 * class Example($param) {}
	 * Now the `$param` will be decorated with 42
	 * class OtherExample($param) {}
	 * This will not be decorated with 42 because is not Example::class
	 * @see [Injection Definitions](https://github.com/rdlowrey/auryn#injection-definitions)
	 */
	AurynResolver::DEFINITIONS	=> [
		Example::class	=> [
			':param'	=> 42,
		]
	],

	/**
	 * As soon as the instance is created you can prepare some action before use the new created instance
	 * This is the same as:
	 * $class = new \stdClass;
	 * $class->param = 42;
	 * echo $class->param;
	 * @see [Prepares and Setter Injection](https://github.com/rdlowrey/auryn#prepares-and-setter-injection)
	 */
	AurynResolver::PREPARATIONS	=> [
		stdClass::class	=> function ( stdClass $class, Injector $injector ) {
			$class->param = 42;
		},
	],

	/**
	 * You can delegate the instantiation for an object to a some kinf of factory
	 * This will be aloways used to get the instante of a class.
	 * @see [Instantiation Delegates](https://github.com/rdlowrey/auryn#instantiation-delegates)
	 */
	AurynResolver::DELEGATIONS	=> [
		ConfigInterface::class	=> [ ConfigFactory::class, 'make']
	],
];

/**
 * Instantiate the Injector
 */
$injector = new Injector();
/**
 * Pass the $injector instance to the AurynResolver::class as first parameter and a
 * Config::class instance at the second parameters with the configuration array.
 */
$app = new AurynResolver( $injector, ConfigFactory::make( $config ) );

/**
 * Call the AurynResolver::resolve() method to do the autowiring of the application
 */
$app->resolve();

/**
 * Now that you have autoload your application dependency you can call $injector for instantiating objects
 * when you need them
 */
$example = $injector->make( Example::class );



//$example2 = $injector->make( Example::class );
//
//$result = $injector->execute( [ $example, 'execute' ] );

//d_footer(
//	$app,
//	$example,
//	$example2,
//	$example !== $example2,
//	$result,
//	$example->getConfig()
//);
