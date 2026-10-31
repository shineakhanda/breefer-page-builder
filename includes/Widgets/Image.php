<?php
/**
 * Image widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a lazy-loaded image.
 */
final class Image extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'image';
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

		$url = $this->str( $settings, 'url' );

		if ( '' === $url ) {
			return '';
		}

		$alt = $this->str( $settings, 'alt' );

		return sprintf(
			'<figure class="br-image"><img src="%1$s" alt="%2$s" loading="lazy" decoding="async" /></figure>',
			esc_url( $url ),
			esc_attr( $alt )
		);
	}
}
