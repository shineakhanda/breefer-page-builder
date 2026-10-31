<?php
/**
 * Map widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Embeds a Google map for an address, with no API key required.
 */
final class MapEmbed extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'map';
	}

	/**
	 * Render.
	 *
	 * @param array<string, mixed> $settings      Settings.
	 * @param string               $children_html Children.
	 * @param string               $node_id       Node id.
	 * @return string
	 */
	public function render( array $settings, string $children_html, string $node_id ): string {
		unset( $children_html, $node_id );

		$address = trim( $this->str( $settings, 'address' ) );

		if ( '' === $address ) {
			return $this->placeholder( __( 'Map', 'breefer' ), __( 'Enter an address to show a map.', 'breefer' ) );
		}

		$zoom   = $this->int( $settings, 'zoom', 1, 20, 14 );
		$height = $this->int( $settings, 'height', 120, 1000, 380 );
		$src    = 'https://www.google.com/maps?q=' . rawurlencode( $address ) . '&z=' . $zoom . '&output=embed';

		return sprintf(
			'<div class="br-map"%1$s><iframe src="%2$s" title="%3$s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>',
			$this->vars( array( '--br-map-height' => $height . 'px' ) ),
			esc_url( $src ),
			esc_attr( $address )
		);
	}
}
