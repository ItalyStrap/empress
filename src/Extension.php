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
	 * @param AurynConfigInterface $application
	 * @return void
	 */
	public function execute( AurynConfigInterface $application );
}
