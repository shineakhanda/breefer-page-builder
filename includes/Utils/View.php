<?php
/**
 * Template rendering helper.
 *
 * @package Breefer
 */

namespace Breefer\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Loads PHP templates with a single, explicit data contract.
 */
final class View {

	/**
	 * Render a template from templates/.
	 *
	 * Templates receive their data as $args.
	 *
	 * @param string               $template Relative template path without extension.
	 * @param array<string, mixed> $args     Template data.
	 * @return void
	 */
	public static function render( string $template, array $args = array() ): void {
		$path = BREEFER_DIR . 'templates/' . trim( $template, '/' ) . '.php';

		if ( ! is_readable( $path ) ) {
			_doing_it_wrong(
				esc_html( __METHOD__ ),
				esc_html(
					sprintf(
						/* translators: %s: template path. */
						__( 'Template "%s" could not be found.', 'breefer' ),
						$template
					)
				),
				esc_html( BREEFER_VERSION )
			);

			return;
		}

		require $path;
	}
}
