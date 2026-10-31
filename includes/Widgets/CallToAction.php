<?php
/**
 * Call to action widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Banner with a headline, supporting text and one or two buttons.
 */
final class CallToAction extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'cta';
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
		$tag   = $this->choice( $settings, 'tag', array( 'h1', 'h2', 'h3', 'h4' ), 'h2' );
		$style = $this->choice( $settings, 'style', array( 'filled', 'outline', 'plain' ), 'filled' );
		$align = $this->choice( $settings, 'layout', array( 'center', 'split' ), 'center' );

		$primary   = $this->link_button( $this->str( $settings, 'buttonText', __( 'Get started', 'breefer' ) ), $this->str( $settings, 'buttonUrl', '#' ) );
		$secondary = $this->link_button( $this->str( $settings, 'button2Text' ), $this->str( $settings, 'button2Url', '#' ), false, 'is-ghost' );

		if ( '' === $title && '' === $text && '' === $primary ) {
			return '';
		}

		$vars = $this->vars(
			array(
				'--br-cta-bg'     => $this->color( $settings, 'ctaBackground' ),
				'--br-cta-text'   => $this->color( $settings, 'textColor' ),
				'--br-cta-button' => $this->color( $settings, 'buttonColor' ),
			)
		);

		$html  = '<div class="br-cta is-' . esc_attr( $style ) . ' is-' . esc_attr( $align ) . '"' . $vars . '>';
		$html .= '<div class="br-cta-copy">';
		$html .= '' !== $title ? '<' . $tag . ' class="br-cta-title">' . esc_html( $title ) . '</' . $tag . '>' : '';
		$html .= '' !== $text ? '<p class="br-cta-text">' . wp_kses_post( $text ) . '</p>' : '';
		$html .= '</div>';
		$html .= '<div class="br-cta-actions">' . $primary . $secondary . '</div>';

		return $html . '</div>';
	}
}
