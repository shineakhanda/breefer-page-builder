<?php
/**
 * Flip box widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Two-sided card that flips on hover or keyboard focus.
 */
final class FlipBox extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'flipbox';
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

		$vars = $this->vars(
			array(
				'--br-flip-height'  => $this->px( $settings, 'height', 120, 800 ),
				'--br-flip-front'   => $this->color( $settings, 'frontBackground' ),
				'--br-flip-back'    => $this->color( $settings, 'backBackground' ),
				'--br-flip-color'   => $this->color( $settings, 'textColor' ),
			)
		);

		$effect = $this->choice( $settings, 'effect', array( 'horizontal', 'vertical', 'fade' ), 'horizontal' );

		$html  = '<div class="br-flipbox is-' . esc_attr( $effect ) . '" tabindex="0"' . $vars . '><div class="br-flipbox-inner">';
		$html .= '<div class="br-flipbox-face is-front">';
		$html .= '<h3>' . esc_html( $this->str( $settings, 'frontTitle', __( 'Front side', 'breefer' ) ) ) . '</h3>';
		$html .= '<p>' . wp_kses_post( $this->str( $settings, 'frontText' ) ) . '</p></div>';
		$html .= '<div class="br-flipbox-face is-back">';
		$html .= '<h3>' . esc_html( $this->str( $settings, 'backTitle', __( 'Back side', 'breefer' ) ) ) . '</h3>';
		$html .= '<p>' . wp_kses_post( $this->str( $settings, 'backText' ) ) . '</p>';
		$html .= $this->link_button( $this->str( $settings, 'buttonText' ), $this->str( $settings, 'buttonUrl', '#' ), false, 'is-small' );

		return $html . '</div></div></div>';
	}
}
