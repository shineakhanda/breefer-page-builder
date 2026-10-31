<?php
/**
 * PSR-4 autoloader fallback.
 *
 * Composer is preferred when available. This autoloader keeps the plugin
 * runnable from a plain source checkout with no vendor directory.
 *
 * @package Breefer
 */

namespace Breefer;

defined( 'ABSPATH' ) || exit;

/**
 * Maps the Breefer namespace onto the includes directory.
 */
final class Autoloader {

	/**
	 * Namespace prefix handled by this loader.
	 */
	private const PREFIX = 'Breefer\\';

	/**
	 * Register the loader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function load( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );

		if ( '' === $relative ) {
			return;
		}

		$path = BREEFER_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
