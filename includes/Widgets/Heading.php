<?php
/**
 * Heading widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a semantic heading.
 */
final class Heading extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'heading';
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

		$text  = $this->str( $settings, 'text' );
		$level = $this->str( $settings, 'level', 'h2' );

		if ( ! in_array( $level, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
			$level = 'h2';
		}

		if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
			return '';
		}

		return sprintf( '<%1$s class="br-heading">%2$s</%1$s>', $level, wp_kses_post( $text ) );
	}
}
