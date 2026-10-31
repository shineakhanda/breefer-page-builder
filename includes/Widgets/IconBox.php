<?php
/**
 * Icon box widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a dashicon, title and description.
 */
final class IconBox extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'iconbox';
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

		$icon        = $this->str( $settings, 'icon', 'star' );
		$title       = $this->str( $settings, 'title' );
		$description = $this->str( $settings, 'description' );

		$html  = '<div class="br-iconbox">';
		$html .= Icons::render( $icon, 'br-iconbox-icon' );

		if ( '' !== trim( $title ) ) {
			$html .= '<h3 class="br-iconbox-title">' . esc_html( $title ) . '</h3>';
		}

		if ( '' !== trim( $description ) ) {
			$html .= '<p class="br-iconbox-text">' . esc_html( $description ) . '</p>';
		}

		return $html . '</div>';
	}
}
