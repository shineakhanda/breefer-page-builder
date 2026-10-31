<?php
/**
 * Icon widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a single library icon.
 */
final class Icon extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'icon';
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

		$icon = Icons::render( $this->str( $settings, 'icon', 'bolt' ), 'br-icon' );

		return '' !== $icon ? '<span class="br-icon-wrap">' . $icon . '</span>' : '';
	}
}
