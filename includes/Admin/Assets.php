<?php
/**
 * Admin assets.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\Utils\Options;
use Breefer\Utils\Palette;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the admin shell assets on Breefer screens only.
 */
final class Assets {

	/**
	 * Screens that load the admin shell.
	 *
	 * @var array<int, string>
	 */
	private const SCREENS = array(
		'toplevel_page_breefer',
		'breefer_page_breefer-settings',
	);

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @param string $hook_suffix Current admin screen.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, self::SCREENS, true ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'breefer-tokens',
			BREEFER_URL . 'assets/css/tokens.css',
			array(),
			$this->version( 'assets/css/tokens.css' )
		);

		wp_enqueue_style(
			'breefer-admin',
			BREEFER_URL . 'assets/css/admin.css',
			array( 'breefer-tokens', 'dashicons' ),
			$this->version( 'assets/css/admin.css' )
		);

		wp_add_inline_style(
			'breefer-tokens',
			Palette::css( (string) Options::get( 'appearance.palette', 'violet' ) )
		);

		wp_enqueue_script(
			'breefer-admin',
			BREEFER_URL . 'assets/js/admin.js',
			array(),
			$this->version( 'assets/js/admin.js' ),
			true
		);

		wp_localize_script(
			'breefer-admin',
			'breeferAdmin',
			array(
				'copied' => __( 'Copied', 'breefer' ),
			)
		);
	}

	/**
	 * Add Breefer body classes.
	 *
	 * @param string $classes Existing classes.
	 * @return string
	 */
	public function body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || ! in_array( $screen->id, self::SCREENS, true ) ) {
			return $classes;
		}

		$classes .= ' breefer-page';

		if ( Palette::is_dark( (string) Options::get( 'appearance.palette', 'violet' ) ) ) {
			$classes .= ' breefer-dark';
		}

		return $classes;
	}

	/**
	 * Cache-busting version for a plugin asset.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	private function version( string $relative ): string {
		$path = BREEFER_DIR . $relative;

		return is_readable( $path ) ? (string) filemtime( $path ) : BREEFER_VERSION;
	}
}
