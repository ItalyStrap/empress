<?php
declare(strict_types=1);

namespace ItalyStrap\Container;

use ItalyStrap\Config\ConfigInterface;

/**
 * Interface Extension
 * @package ItalyStrap\Container
 */
interface Extension {

	/**
	 * @return string
	 */
	public function name(): string;

	/**
	 * @param AurynResolver $application
	 */
	public function execute( AurynResolver $application );
}
