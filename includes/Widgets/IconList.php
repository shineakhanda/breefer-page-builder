<?php
/**
 * Icon list widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a list with a shared library icon.
 */
final class IconList extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'iconlist';
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

		$raw   = $this->str( $settings, 'items' );
		$lines = preg_split( '/\r\n|\r|\n/', $raw );

		if ( ! is_array( $lines ) ) {
			return '';
		}

		$icon = Icons::render( $this->str( $settings, 'icon', 'check-circle' ), 'br-icon-list-icon' );
		$html = '<ul class="br-icon-list">';

		foreach ( $lines as $line ) {
			$line = trim( wp_strip_all_tags( (string) $line ) );

			if ( '' === $line ) {
				continue;
			}

			$html .= '<li>' . $icon . '<span>' . esc_html( $line ) . '</span></li>';
		}

		return $html . '</ul>';
	}
}
