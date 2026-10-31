<?php
/**
 * Built-in icon library.
 *
 * @package Breefer
 */

namespace Breefer\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * A dependency-free SVG icon set shared by the renderer and the editor.
 *
 * Register additional icons with the breefer/icons filter. Each entry needs a
 * label and a list of SVG path definitions on a 24x24 grid.
 */
final class Icons {

	/**
	 * All icons.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		$icons = array(
			'arrow-right'  => array(
				'label' => __( 'Arrow right', 'breefer' ),
				'paths' => array( 'M4 12h15', 'M13 6l6 6-6 6' ),
			),
			'arrow-left'   => array(
				'label' => __( 'Arrow left', 'breefer' ),
				'paths' => array( 'M20 12H5', 'M11 6l-6 6 6 6' ),
			),
			'arrow-up'     => array(
				'label' => __( 'Arrow up', 'breefer' ),
				'paths' => array( 'M12 20V5', 'M6 11l6-6 6 6' ),
			),
			'arrow-down'   => array(
				'label' => __( 'Arrow down', 'breefer' ),
				'paths' => array( 'M12 4v15', 'M6 13l6 6 6-6' ),
			),
			'check'        => array(
				'label' => __( 'Check', 'breefer' ),
				'paths' => array( 'M4 12l5 5L20 6' ),
			),
			'check-circle' => array(
				'label' => __( 'Check circle', 'breefer' ),
				'paths' => array( 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20', 'm8 12 3 3 5-6' ),
			),
			'close'        => array(
				'label' => __( 'Close', 'breefer' ),
				'paths' => array( 'M6 6l12 12', 'M18 6 6 18' ),
			),
			'plus'         => array(
				'label' => __( 'Plus', 'breefer' ),
				'paths' => array( 'M12 5v14', 'M5 12h14' ),
			),
			'minus'        => array(
				'label' => __( 'Minus', 'breefer' ),
				'paths' => array( 'M5 12h14' ),
			),
			'star'         => array(
				'label' => __( 'Star', 'breefer' ),
				'paths' => array( 'm12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z' ),
				'fill'  => true,
			),
			'star-outline' => array(
				'label' => __( 'Star outline', 'breefer' ),
				'paths' => array( 'm12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z' ),
			),
			'heart'        => array(
				'label' => __( 'Heart', 'breefer' ),
				'paths' => array( 'M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21l8.8-8.3a5 5 0 0 0 0-7.1Z' ),
			),
			'bolt'         => array(
				'label' => __( 'Bolt', 'breefer' ),
				'paths' => array( 'M13 2 4 14h6l-1 8 9-12h-6l1-8Z' ),
			),
			'shield'       => array(
				'label' => __( 'Shield', 'breefer' ),
				'paths' => array( 'M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z' ),
			),
			'lock'         => array(
				'label' => __( 'Lock', 'breefer' ),
				'paths' => array( 'M6 10V7a6 6 0 0 1 12 0v3', 'M4 10h16v11H4Z' ),
			),
			'mail'         => array(
				'label' => __( 'Mail', 'breefer' ),
				'paths' => array( 'M3 5h18v14H3Z', 'm3 6 9 7 9-7' ),
			),
			'phone'        => array(
				'label' => __( 'Phone', 'breefer' ),
				'paths' => array( 'M6.6 3h3l1.5 4-2 1.5a14 14 0 0 0 6.4 6.4l1.5-2 4 1.5v3a2 2 0 0 1-2.2 2A18 18 0 0 1 4.6 5.2 2 2 0 0 1 6.6 3Z' ),
			),
			'map-pin'      => array(
				'label' => __( 'Map pin', 'breefer' ),
				'paths' => array( 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z', 'M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z' ),
			),
			'user'         => array(
				'label' => __( 'User', 'breefer' ),
				'paths' => array( 'M20 21a8 8 0 0 0-16 0', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z' ),
			),
			'users'        => array(
				'label' => __( 'Users', 'breefer' ),
				'paths' => array( 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'M22 21v-2a4 4 0 0 0-3-3.9', 'M16 3.1a4 4 0 0 1 0 7.8' ),
			),
			'calendar'     => array(
				'label' => __( 'Calendar', 'breefer' ),
				'paths' => array( 'M4 5h16v16H4Z', 'M8 3v4', 'M16 3v4', 'M4 10h16' ),
			),
			'clock'        => array(
				'label' => __( 'Clock', 'breefer' ),
				'paths' => array( 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20', 'M12 7v5l3 2' ),
			),
			'search'       => array(
				'label' => __( 'Search', 'breefer' ),
				'paths' => array( 'M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z', 'm16 16 5 5' ),
			),
			'sliders'      => array(
				'label' => __( 'Sliders', 'breefer' ),
				'paths' => array( 'M4 6h16', 'M4 12h16', 'M4 18h16', 'M9 6v0', 'M15 12v0', 'M7 18v0' ),
			),
			'home'         => array(
				'label' => __( 'Home', 'breefer' ),
				'paths' => array( 'm3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z' ),
			),
			'cart'         => array(
				'label' => __( 'Cart', 'breefer' ),
				'paths' => array( 'M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6', 'M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z', 'M18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z' ),
			),
			'bag'          => array(
				'label' => __( 'Bag', 'breefer' ),
				'paths' => array( 'M6 8h12l1 13H5L6 8Z', 'M9 8V6a3 3 0 0 1 6 0v2' ),
			),
			'credit-card'  => array(
				'label' => __( 'Credit card', 'breefer' ),
				'paths' => array( 'M3 6h18v12H3Z', 'M3 10h18' ),
			),
			'tag'          => array(
				'label' => __( 'Tag', 'breefer' ),
				'paths' => array( 'M20 12 12 20l-8-8V4h8l8 8Z', 'M8.5 8.5h.01' ),
			),
			'gift'         => array(
				'label' => __( 'Gift', 'breefer' ),
				'paths' => array( 'M20 12v9H4v-9', 'M2 8h20v4H2Z', 'M12 8v13', 'M12 8C10 8 7 7 7 5a2 2 0 0 1 4 0c0 1 1 3 1 3Z', 'M12 8c2 0 5-1 5-3a2 2 0 0 0-4 0c0 1-1 3-1 3Z' ),
			),
			'globe'        => array(
				'label' => __( 'Globe', 'breefer' ),
				'paths' => array( 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20', 'M2 12h20', 'M12 2c3 3.5 4.5 6.9 4.5 10S15 18.5 12 22c-3-3.5-4.5-6.9-4.5-10S9 5.5 12 2Z' ),
			),
			'cloud'        => array(
				'label' => __( 'Cloud', 'breefer' ),
				'paths' => array( 'M17.5 19a4.5 4.5 0 0 0 .4-9A6 6 0 0 0 6.2 9.3 4.5 4.5 0 0 0 6.5 19h11Z' ),
			),
			'download'     => array(
				'label' => __( 'Download', 'breefer' ),
				'paths' => array( 'M12 3v12', 'm7 10 5 5 5-5', 'M4 21h16' ),
			),
			'upload'       => array(
				'label' => __( 'Upload', 'breefer' ),
				'paths' => array( 'M12 17V5', 'm7 10 5-5 5 5', 'M4 21h16' ),
			),
			'link'         => array(
				'label' => __( 'Link', 'breefer' ),
				'paths' => array( 'M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5', 'M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5' ),
			),
			'play'         => array(
				'label' => __( 'Play', 'breefer' ),
				'paths' => array( 'M8 5v14l11-7L8 5Z' ),
				'fill'  => true,
			),
			'image'        => array(
				'label' => __( 'Image', 'breefer' ),
				'paths' => array( 'M3 4h18v16H3Z', 'm3 16 5-5 4 4 3-3 6 6', 'M8.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z' ),
			),
			'camera'       => array(
				'label' => __( 'Camera', 'breefer' ),
				'paths' => array( 'M4 8h3l2-3h6l2 3h3v12H4Z', 'M12 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z' ),
			),
			'code'         => array(
				'label' => __( 'Code', 'breefer' ),
				'paths' => array( 'm8 8-5 4 5 4', 'm16 8 5 4-5 4' ),
			),
			'layers'       => array(
				'label' => __( 'Layers', 'breefer' ),
				'paths' => array( 'm12 3 9 5-9 5-9-5 9-5Z', 'm3 13 9 5 9-5' ),
			),
			'grid'         => array(
				'label' => __( 'Grid', 'breefer' ),
				'paths' => array( 'M3 3h7v7H3Z', 'M14 3h7v7h-7Z', 'M3 14h7v7H3Z', 'M14 14h7v7h-7Z' ),
			),
			'layout'       => array(
				'label' => __( 'Layout', 'breefer' ),
				'paths' => array( 'M3 3h18v18H3Z', 'M3 9h18', 'M9 9v12' ),
			),
			'menu'         => array(
				'label' => __( 'Menu', 'breefer' ),
				'paths' => array( 'M4 6h16', 'M4 12h16', 'M4 18h16' ),
			),
			'message'      => array(
				'label' => __( 'Message', 'breefer' ),
				'paths' => array( 'M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z' ),
			),
			'send'         => array(
				'label' => __( 'Send', 'breefer' ),
				'paths' => array( 'm22 2-7 20-4-9-9-4 20-7Z', 'M22 2 11 13' ),
			),
			'sparkles'     => array(
				'label' => __( 'Sparkles', 'breefer' ),
				'paths' => array( 'm12 3 1.8 3.7L18 8.5l-3.2 2.8.9 4.2L12 13.6l-3.7 1.9.9-4.2L6 8.5l4.2-.8L12 3Z', 'M19 3v4', 'M17 5h4' ),
			),
			'sun'          => array(
				'label' => __( 'Sun', 'breefer' ),
				'paths' => array( 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'M12 2v2', 'M12 20v2', 'M2 12h2', 'M20 12h2', 'm5 5 1.5 1.5', 'm17.5 17.5 1.5 1.5', 'm19 5-1.5 1.5', 'm6.5 17.5-1.5 1.5' ),
			),
			'moon'         => array(
				'label' => __( 'Moon', 'breefer' ),
				'paths' => array( 'M21 12.8A8.5 8.5 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z' ),
			),
			'award'        => array(
				'label' => __( 'Award', 'breefer' ),
				'paths' => array( 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z', 'm8.5 13.5-2 8 5.5-3 5.5 3-2-8' ),
			),
			'target'       => array(
				'label' => __( 'Target', 'breefer' ),
				'paths' => array( 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20', 'M12 6a6 6 0 1 0 0 12 6 6 0 0 0 0-12', 'M12 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4' ),
			),
		);

		/**
		 * Filter the editor icon library.
		 *
		 * @param array $icons Icon definitions keyed by slug.
		 */
		$filtered = apply_filters( 'breefer/icons', $icons );

		return is_array( $filtered ) ? $filtered : $icons;
	}

	/**
	 * Read one icon.
	 *
	 * @param string $slug Icon slug.
	 * @return array<string, mixed>|null
	 */
	public static function get( string $slug ): ?array {
		$icons = self::all();
		$slug  = sanitize_key( $slug );

		return $icons[ $slug ] ?? null;
	}

	/**
	 * Render an icon as inline SVG.
	 *
	 * @param string $slug  Icon slug.
	 * @param string $class CSS class.
	 * @return string
	 */
	public static function render( string $slug, string $class = 'br-icon' ): string {
		$icon = self::get( $slug );

		if ( null === $icon || ! isset( $icon['paths'] ) || ! is_array( $icon['paths'] ) ) {
			return '';
		}

		$fill      = ! empty( $icon['fill'] );
		$attributes = $fill
			? 'fill="currentColor" stroke="none"'
			: 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';

		$paths = '';

		foreach ( $icon['paths'] as $path ) {
			$paths .= '<path d="' . esc_attr( (string) $path ) . '"/>';
		}

		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="1em" height="1em" ' . $attributes . ' aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}
}
