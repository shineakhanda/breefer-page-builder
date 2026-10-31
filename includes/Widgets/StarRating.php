<?php
/**
 * Star rating widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders an accessible five-star rating.
 */
final class StarRating extends AbstractWidget {

	/**
	 * Star path.
	 */
	private const STAR = 'm12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z';

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'stars';
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

		$rating = max( 0, min( 5, $this->num( $settings, 'rating', 5 ) ) );
		$html   = '<div class="br-stars" role="img" aria-label="' . esc_attr(
			sprintf(
				/* translators: %s: rating out of five. */
				__( 'Rated %s out of 5', 'breefer' ),
				(string) $rating
			)
		) . '">';

		for ( $index = 1; $index <= 5; $index++ ) {
			$filled = $index <= $rating;

			$html .= sprintf(
				'<svg class="br-star%1$s" viewBox="0 0 24 24" width="1em" height="1em" %2$s aria-hidden="true" focusable="false"><path d="%3$s"/></svg>',
				$filled ? ' is-filled' : '',
				$filled ? 'fill="currentColor" stroke="none"' : 'fill="none" stroke="currentColor" stroke-width="1.5"',
				esc_attr( self::STAR )
			);
		}

		return $html . '</div>';
	}
}
