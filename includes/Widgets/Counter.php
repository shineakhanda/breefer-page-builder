<?php
/**
 * Animated counter widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Counts up to a number when it scrolls into view.
 */
final class Counter extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'counter';
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

		$start    = $this->num( $settings, 'start', 0 );
		$end      = $this->num( $settings, 'end', 100 );
		$duration = $this->int( $settings, 'duration', 200, 20000, 2000 );
		$prefix   = $this->str( $settings, 'prefix' );
		$suffix   = $this->str( $settings, 'suffix' );
		$title    = $this->str( $settings, 'title' );
		$separate = $this->bool( $settings, 'separator' );
		$decimals = $this->int( $settings, 'decimals', 0, 4, 0 );

		$vars = $this->vars(
			array(
				'--br-counter-color' => $this->color( $settings, 'numberColor' ),
				'--br-counter-size'  => $this->px( $settings, 'numberSize', 12, 240 ),
				'--br-counter-title' => $this->color( $settings, 'titleColor' ),
			)
		);

		$html  = sprintf(
			'<div class="br-counter" data-breefer-counter data-start="%1$s" data-end="%2$s" data-duration="%3$d" data-decimals="%4$d" data-separator="%5$d"%6$s>',
			esc_attr( (string) $start ),
			esc_attr( (string) $end ),
			$duration,
			$decimals,
			$separate ? 1 : 0,
			$vars
		);
		$html .= '<div class="br-counter-number">';
		$html .= '' !== $prefix ? '<span class="br-counter-prefix">' . esc_html( $prefix ) . '</span>' : '';
		$html .= '<span class="br-counter-value">' . esc_html( number_format( $end, $decimals, '.', $separate ? ',' : '' ) ) . '</span>';
		$html .= '' !== $suffix ? '<span class="br-counter-suffix">' . esc_html( $suffix ) . '</span>' : '';
		$html .= '</div>';
		$html .= '' !== $title ? '<div class="br-counter-label">' . esc_html( $title ) . '</div>' : '';
		$html .= '</div>';

		return $html;
	}
}
