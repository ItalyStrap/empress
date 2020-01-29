<?php
declare(strict_types=1);

namespace ItalyStrap\Empress;

/**
 * Interface ApplicationInterface
 * @package ItalyStrap\Empress
 */
interface AurynResolverInterface {

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
