<?php
/**
 * Testimonial widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Customer quote with avatar, name and optional star rating.
 */
final class Testimonial extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'testimonial';
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

		$quote = $this->str( $settings, 'content' );

		if ( '' === trim( wp_strip_all_tags( $quote ) ) ) {
			return '';
		}

		$name   = $this->str( $settings, 'name' );
		$role   = $this->str( $settings, 'role' );
		$image  = $this->str( $settings, 'image' );
		$rating = max( 0, min( 5, $this->num( $settings, 'rating', 5 ) ) );
		$layout = $this->choice( $settings, 'layout', array( 'card', 'plain', 'centered' ), 'card' );

		$vars = $this->vars(
			array(
				'--br-t-accent' => $this->color( $settings, 'accent' ),
				'--br-t-bg'     => $this->color( $settings, 'cardBackground' ),
				'--br-t-text'   => $this->color( $settings, 'textColor' ),
			)
		);

		$html = '<figure class="br-testimonial is-' . esc_attr( $layout ) . '"' . $vars . '>';

		if ( $rating > 0 ) {
			$html .= '<div class="br-testimonial-stars" role="img" aria-label="' . esc_attr(
				sprintf(
					/* translators: %s: rating out of five. */
					__( 'Rated %s out of 5', 'breefer' ),
					(string) $rating
				)
			) . '">';

			for ( $i = 1; $i <= 5; $i++ ) {
				$html .= '<svg viewBox="0 0 24 24" class="' . ( $i <= $rating ? 'is-on' : '' ) . '" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/></svg>';
			}

			$html .= '</div>';
		}

		$html .= '<blockquote class="br-testimonial-quote">' . wp_kses_post( $quote ) . '</blockquote>';

		if ( '' !== $name || '' !== $image ) {
			$html .= '<figcaption class="br-testimonial-author">';

			if ( '' !== $image ) {
				$html .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" width="48" height="48" loading="lazy">';
			}

			$html .= '<span>';
			$html .= '' !== $name ? '<strong>' . esc_html( $name ) . '</strong>' : '';
			$html .= '' !== $role ? '<small>' . esc_html( $role ) . '</small>' : '';
			$html .= '</span></figcaption>';
		}

		return $html . '</figure>';
	}
}
