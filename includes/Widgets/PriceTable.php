<?php
/**
 * Price table widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Pricing plan card with a feature list and call to action.
 */
final class PriceTable extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'pricetable';
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

		$title    = $this->str( $settings, 'title' );
		$subtitle = $this->str( $settings, 'subtitle' );
		$currency = $this->str( $settings, 'currency', '$' );
		$price    = $this->str( $settings, 'price', '29' );
		$period   = $this->str( $settings, 'period' );
		$badge    = $this->str( $settings, 'badge' );
		$featured = $this->bool( $settings, 'featured' );

		$vars = $this->vars(
			array(
				'--br-p-accent' => $this->color( $settings, 'accent' ),
				'--br-p-bg'     => $this->color( $settings, 'cardBackground' ),
				'--br-p-text'   => $this->color( $settings, 'textColor' ),
			)
		);

		$html = '<div class="br-pricetable' . ( $featured ? ' is-featured' : '' ) . '"' . $vars . '>';

		if ( '' !== $badge ) {
			$html .= '<span class="br-pricetable-badge">' . esc_html( $badge ) . '</span>';
		}

		$html .= '<div class="br-pricetable-head">';
		$html .= '' !== $title ? '<h3 class="br-pricetable-title">' . esc_html( $title ) . '</h3>' : '';
		$html .= '' !== $subtitle ? '<p class="br-pricetable-subtitle">' . esc_html( $subtitle ) . '</p>' : '';
		$html .= '</div>';

		$html .= '<div class="br-pricetable-price"><span class="br-pricetable-currency">' . esc_html( $currency ) . '</span>'
			. '<span class="br-pricetable-amount">' . esc_html( $price ) . '</span>'
			. ( '' !== $period ? '<span class="br-pricetable-period">' . esc_html( $period ) . '</span>' : '' )
			. '</div>';

		$features = $this->lines( $settings, 'features' );

		if ( array() !== $features ) {
			$html .= '<ul class="br-pricetable-features">';

			foreach ( $features as $feature ) {
				// A leading "-" marks a feature the plan does not include.
				$off   = str_starts_with( $feature, '-' );
				$label = $off ? trim( substr( $feature, 1 ) ) : $feature;

				$html .= '<li class="' . ( $off ? 'is-off' : 'is-on' ) . '"><span class="br-pricetable-mark" aria-hidden="true"></span>'
					. '<span>' . esc_html( $label ) . '</span></li>';
			}

			$html .= '</ul>';
		}

		$button = $this->link_button(
			$this->str( $settings, 'buttonText', __( 'Get started', 'breefer' ) ),
			$this->str( $settings, 'buttonUrl', '#' ),
			$this->bool( $settings, 'newTab' )
		);

		if ( '' !== $button ) {
			$html .= '<div class="br-pricetable-cta">' . $button . '</div>';
		}

		return $html . '</div>';
	}
}
