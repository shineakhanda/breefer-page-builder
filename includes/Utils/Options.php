<?php
/**
 * Plugin settings access.
 *
 * @package Breefer
 */

namespace Breefer\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Typed access to the single Breefer settings option.
 */
final class Options {

	/**
	 * Option name.
	 */
	public const OPTION = 'breefer_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'appearance'  => array(
				'palette' => 'violet',
			),
			'editor'      => array(
				'autosave_interval' => 30,
				'history_limit'     => 50,
				'canvas_width'      => 1200,
				'default_editor'    => true,
			),
			'performance' => array(
				'critical_css'   => true,
				'lazy_islands'   => true,
				'render_cache'   => true,
				'prefetch_links' => false,
			),
			'access'      => array(
				'role' => 'administrator',
			),
			'design'      => DesignSystem::defaults(),
			'woocommerce' => array(
				'ajax_add_to_cart' => true,
				'cart_fragments'   => true,
				'sticky_cart'      => true,
			),
		);
	}

	/**
	 * Read the merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return self::merge( self::defaults(), $stored );
	}

	/**
	 * Read a nested value using dot notation.
	 *
	 * @param string $path    Dot separated path.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( string $path, mixed $default = null ): mixed {
		$value = self::all();

		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				return $default;
			}

			$value = $value[ $key ];
		}

		return $value;
	}

	/**
	 * Persist a partial settings update.
	 *
	 * @param array<string, mixed> $values Partial values.
	 * @return bool
	 */
	public static function update( array $values ): bool {
		return update_option( self::OPTION, self::merge( self::all(), $values ) );
	}

	/**
	 * Recursively merge stored values over defaults.
	 *
	 * @param array<string, mixed> $base     Defaults.
	 * @param array<string, mixed> $override Stored values.
	 * @return array<string, mixed>
	 */
	private static function merge( array $base, array $override ): array {
		foreach ( $override as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = self::merge( $base[ $key ], $value );
				continue;
			}

			$base[ $key ] = $value;
		}

		return $base;
	}
}
