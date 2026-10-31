<?php
/**
 * Shape widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * A decorative box, circle or pill. Fill, border, radius, shadow and size come
 * from the generic node settings, so this only outputs an empty block.
 */
final class Shape extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'shape';
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
		unset( $settings, $children_html, $node_id );

		return '<div class="br-shape" aria-hidden="true"></div>';
	}
}
