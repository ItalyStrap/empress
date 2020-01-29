<?php
declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Config\ConfigInterface;

/**
 * Interface Extension
 * @package ItalyStrap\Empress
 */
interface Extension {

	/**
	 * @return string
	 */
	public function name(): string;

	/**
	 * @param AurynResolver $application
	 */
	public function execute( AurynResolverInterface $application );
}
