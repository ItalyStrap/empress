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
	private $config;

	/**
	 * Application constructor.
	 * @param Config $config
	 * @param Injector $injector
	 */
	public function __construct( Config $config, Injector $injector ) {
		$this->injector = $injector;
		$this->config = $config;
	}

	public function register() {
		foreach ( $this->config as $config ) {

		}
	}
}
