<?php
/**
 * Text widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a rich text block.
 */
final class Text extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'text';
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

		$content = $this->str( $settings, 'content' );

		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			return '';
		}

		return '<div class="br-text">' . wp_kses_post( wpautop( $content ) ) . '</div>';
	}
}
