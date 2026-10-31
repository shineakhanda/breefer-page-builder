<?php
/**
 * Button widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a call-to-action button.
 */
final class Button extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'button';
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

		$text = $this->str( $settings, 'text', __( 'Click here', 'breefer' ) );
		$url  = $this->str( $settings, 'url', '#' );

		if ( '' === trim( $text ) ) {
			return '';
		}

		$target = $this->bool( $settings, 'newTab' ) ? ' target="_blank" rel="noopener noreferrer"' : '';

		return sprintf(
			'<div class="br-button-wrap"><a class="br-btn" href="%1$s"%2$s>%3$s</a></div>',
			esc_url( $url ),
			$target,
			esc_html( $text )
		);
	}
}
