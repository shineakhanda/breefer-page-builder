<?php
/**
 * Countdown widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Counts down to a date in the site's timezone.
 */
final class Countdown extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'countdown';
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

		$raw = $this->str( $settings, 'dueDate' );

		try {
			$due = new \DateTimeImmutable( '' !== $raw ? $raw : '+7 days', wp_timezone() );
		} catch ( \Exception $e ) {
			return $this->placeholder( __( 'Countdown', 'breefer' ), __( 'Enter a valid date such as 2026-12-31 23:59.', 'breefer' ) );
		}

		$due_ts    = $due->getTimestamp();
		$remaining = max( 0, $due_ts - time() );
		$expired   = $this->str( $settings, 'expiredText', __( 'This offer has ended.', 'breefer' ) );

		$units = array(
			'days'    => array( (int) floor( $remaining / DAY_IN_SECONDS ), __( 'Days', 'breefer' ) ),
			'hours'   => array( (int) floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ), __( 'Hours', 'breefer' ) ),
			'minutes' => array( (int) floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ), __( 'Minutes', 'breefer' ) ),
			'seconds' => array( $remaining % MINUTE_IN_SECONDS, __( 'Seconds', 'breefer' ) ),
		);

		$vars = $this->vars(
			array(
				'--br-cd-color' => $this->color( $settings, 'numberColor' ),
				'--br-cd-bg'    => $this->color( $settings, 'boxBackground' ),
				'--br-cd-label' => $this->color( $settings, 'labelColor' ),
			)
		);

		$html = sprintf(
			'<div class="br-countdown" data-breefer-countdown data-due="%1$d" data-expired="%2$s"%3$s>',
			$due_ts,
			esc_attr( $expired ),
			$vars
		);

		foreach ( $units as $unit => $data ) {
			$html .= sprintf(
				'<div class="br-countdown-unit" data-unit="%1$s"><span class="br-countdown-number">%2$s</span>%3$s</div>',
				esc_attr( $unit ),
				esc_html( str_pad( (string) $data[0], 2, '0', STR_PAD_LEFT ) ),
				$this->bool( $settings, 'hideLabels' ) ? '' : '<span class="br-countdown-label">' . esc_html( $data[1] ) . '</span>'
			);
		}

		return $html . '</div>';
	}
}
