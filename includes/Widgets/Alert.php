<?php
/**
 * Alert widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Info / success / warning / danger notice, optionally dismissible.
 */
final class Alert extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'alert';
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

		$title = $this->str( $settings, 'title' );
		$text  = $this->str( $settings, 'description' );

		if ( '' === $title && '' === $text ) {
			return '';
		}

		$type = $this->choice( $settings, 'type', array( 'info', 'success', 'warning', 'danger' ), 'info' );

		$html  = '<div class="br-alert is-' . esc_attr( $type ) . '" role="' . ( 'danger' === $type ? 'alert' : 'status' ) . '">';
		$html .= '<div class="br-alert-body">';
		$html .= '' !== $title ? '<strong class="br-alert-title">' . esc_html( $title ) . '</strong>' : '';
		$html .= '' !== $text ? '<span class="br-alert-text">' . wp_kses_post( $text ) . '</span>' : '';
		$html .= '</div>';

		if ( $this->bool( $settings, 'dismissible' ) ) {
			$html .= '<button type="button" class="br-alert-close" data-breefer-dismiss aria-label="' . esc_attr__( 'Dismiss', 'breefer' ) . '">&times;</button>';
		}

		return $html . '</div>';
	}
}
