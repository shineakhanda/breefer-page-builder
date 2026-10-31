<?php
/**
 * WooCommerce mini cart widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Compatibility\WooCommerce as WooCompat;
use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Cart icon with a live item count and a dropdown mini cart. Updates through
 * WooCommerce cart fragments, so AJAX add-to-cart refreshes it automatically.
 */
final class WooMiniCart extends AbstractWidget {

	/**
	 * Bag icon path.
	 */
	private const ICON = 'M6 7h12l1 13H5L6 7Zm3 0V6a3 3 0 0 1 6 0v1';

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'woocart';
	}

	/**
	 * Markup for the count badge, shared with the fragments filter.
	 *
	 * @param int $count Item count.
	 * @return string
	 */
	public static function count_html( int $count ): string {
		return '<span class="br-cart-count' . ( 0 === $count ? ' is-empty' : '' ) . '">' . esc_html( (string) $count ) . '</span>';
	}

	/**
	 * Markup for the subtotal, shared with the fragments filter.
	 *
	 * @param string $total Formatted subtotal HTML.
	 * @return string
	 */
	public static function total_html( string $total ): string {
		return '<span class="br-cart-total">' . wp_kses_post( $total ) . '</span>';
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

		if ( ! WooCompat::is_active() || ! function_exists( 'WC' ) ) {
			return '';
		}

		if ( Context::is_preview() || null === WC()->cart ) {
			return '<div class="br-cart-widget"><a class="br-cart-toggle" href="#">' . $this->icon() . self::count_html( 2 ) . self::total_html( '$58.00' ) . '</a></div>';
		}

		WooCompat::enqueue_assets();

		$mode  = $this->choice( $settings, 'mode', array( 'dropdown', 'icon', 'full' ), 'dropdown' );
		$count = (int) WC()->cart->get_cart_contents_count();
		$mini  = '';

		if ( function_exists( 'woocommerce_mini_cart' ) && 'icon' !== $mode ) {
			ob_start();
			woocommerce_mini_cart();
			$mini = (string) ob_get_clean();
		}

		if ( 'full' === $mode ) {
			return '<div class="br-woo-cart"><div class="widget_shopping_cart_content">' . $mini . '</div></div>';
		}

		$vars = $this->vars(
			array(
				'--br-cart-color'  => $this->color( $settings, 'iconColor' ),
				'--br-cart-badge'  => $this->color( $settings, 'badgeColor' ),
				'--br-cart-size'   => $this->px( $settings, 'iconSize', 14, 64 ),
			)
		);

		$html  = '<div class="br-cart-widget is-' . esc_attr( $mode ) . '"' . $vars . '>';
		$html .= '<a class="br-cart-toggle" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr__( 'View cart', 'breefer' ) . '">';
		$html .= $this->icon() . self::count_html( $count );
		$html .= $this->bool( $settings, 'showTotal' ) ? self::total_html( WC()->cart->get_cart_subtotal() ) : '';
		$html .= '</a>';

		if ( 'dropdown' === $mode ) {
			$html .= '<div class="br-cart-dropdown"><div class="widget_shopping_cart_content">' . $mini . '</div></div>';
		}

		return $html . '</div>';
	}

	/**
	 * Bag icon.
	 *
	 * @return string
	 */
	private function icon(): string {
		return '<svg class="br-cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( self::ICON ) . '"/></svg>';
	}
}
