<?php
/**
 * Frontend asset loader.
 *
 * @package Breefer
 */

namespace Breefer\Frontend;

use Breefer\Utils\DesignSystem;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the frontend stylesheet, design tokens and enhancement script.
 *
 * Safe to call repeatedly and late: it is invoked both from wp_enqueue_scripts
 * (no flash of unstyled content) and by the renderer as a fallback.
 */
final class FrontendAssets {

	/**
	 * Whether the assets were already queued.
	 */
	private static bool $queued = false;

	/**
	 * Queue the assets once per request.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( self::$queued ) {
			return;
		}

		self::$queued = true;

		wp_enqueue_style(
			'breefer-frontend',
			BREEFER_URL . 'assets/css/frontend.css',
			array(),
			self::version( 'assets/css/frontend.css' )
		);

		wp_enqueue_style(
			'breefer-widgets',
			BREEFER_URL . 'assets/css/widgets.css',
			array( 'breefer-frontend' ),
			self::version( 'assets/css/widgets.css' )
		);

		wp_add_inline_style( 'breefer-frontend', DesignSystem::css() );

		wp_enqueue_script(
			'breefer-frontend-js',
			BREEFER_URL . 'assets/js/frontend.js',
			array(),
			self::version( 'assets/js/frontend.js' ),
			true
		);

		wp_localize_script(
			'breefer-frontend-js',
			'breeferFront',
			array(
				'restUrl' => esc_url_raw( rest_url( 'breefer/v1/' ) ),
				'i18n'    => array(
					'sending' => __( 'Sending…', 'breefer' ),
					'error'   => __( 'Something went wrong. Please try again.', 'breefer' ),
					'days'    => __( 'Days', 'breefer' ),
					'hours'   => __( 'Hours', 'breefer' ),
					'minutes' => __( 'Minutes', 'breefer' ),
					'seconds' => __( 'Seconds', 'breefer' ),
				),
			)
		);
	}

	/**
	 * File modification time used for cache busting.
	 *
	 * @param string $relative Path relative to the plugin.
	 * @return string
	 */
	private static function version( string $relative ): string {
		$path = BREEFER_DIR . $relative;

		return is_readable( $path ) ? (string) filemtime( $path ) : BREEFER_VERSION;
	}
}
