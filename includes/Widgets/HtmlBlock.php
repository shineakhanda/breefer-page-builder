<?php
/**
 * HTML / shortcode widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders sanitised HTML and expands shortcodes.
 *
 * HTML is filtered with wp_kses_post, so scripts and inline event handlers are
 * removed. Anything that needs scripts belongs in a shortcode or a block.
 */
final class HtmlBlock extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'html';
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

		$html = trim( $this->str( $settings, 'html' ) );

		if ( '' === $html ) {
			return $this->placeholder( __( 'HTML & shortcode', 'breefer' ), __( 'Add markup or a [shortcode].', 'breefer' ) );
		}

		return '<div class="br-html">' . do_shortcode( wp_kses_post( $html ) ) . '</div>';
	}
}
