<?php
/**
 * Progress bar widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a labelled progress bar.
 */
final class ProgressBar extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'progress';
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

		$label   = $this->str( $settings, 'label' );
		$value   = max( 0, min( 100, $this->num( $settings, 'value', 60 ) ) );
		$show    = $this->bool( $settings, 'showValue' );
		$heading = '';

		if ( '' !== trim( $label ) || $show ) {
			$heading = '<div class="br-progress-head"><span>' . esc_html( $label ) . '</span>';

			if ( $show ) {
				$heading .= '<span>' . esc_html( (string) round( $value ) ) . '%</span>';
			}

			$heading .= '</div>';
		}

		return '<div class="br-progress">' . $heading . '<div class="br-progress-track"><div class="br-progress-fill" style="width:' . esc_attr( (string) $value ) . '%"></div></div></div>';
	}
}
