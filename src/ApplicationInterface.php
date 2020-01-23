<?php
declare(strict_types=1);

namespace ItalyStrap\Container;

/**
 * Interface ApplicationInterface
 * @package ItalyStrap\Container
 */
interface ApplicationInterface {

	/**
	 *
	 */
	public function resolve();

	/**
	 * @param Extension ...$extensions
	 */
	public function extend( Extension ...$extensions );

	/**
	 * @param string $key
	 * @param callable $callback
	 * @return void
	 */
	public function walk( string $key, callable $callback );
}
