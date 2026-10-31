<?php
/**
 * Single-product helper widgets (tabs, status, related, ordering, sticky bar).
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Compatibility\WooCommerce as WooCompat;
use Breefer\Utils\Context;
use Breefer\Utils\Options;

defined( 'ABSPATH' ) || exit;

/**
 * One class, several small WooCommerce widgets selected by type:
 * wootabs, woostatus, woorelated, wooordering and woosticky.
 */
final class WooProductExtras extends AbstractWidget {

	/**
	 * Supported types.
	 */
	public const TYPES = array( 'wootabs', 'woostatus', 'woorelated', 'wooordering', 'woosticky' );

	/**
	 * Widget type handled by this instance.
	 */
	private string $kind;

	/**
	 * Constructor.
	 *
	 * @param string $kind One of self::TYPES.
	 */
	public function __construct( string $kind = 'woostatus' ) {
		$this->kind = in_array( $kind, self::TYPES, true ) ? $kind : 'woostatus';
	}

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return $this->kind;
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

		if ( ! WooCompat::is_active() ) {
			return '';
		}

		WooCompat::enqueue_assets();

		switch ( $this->kind ) {
			case 'wootabs':
				return $this->tabs();
			case 'woorelated':
				return $this->related( $settings );
			case 'wooordering':
				return $this->ordering( $settings );
			case 'woosticky':
				return $this->sticky( $settings );
			case 'woostatus':
			default:
				return $this->status( $settings );
		}
	}

	/**
	 * Description / additional information / reviews tabs.
	 *
	 * @return string
	 */
	private function tabs(): string {
		$product = Context::product();

		if ( null === $product || ! function_exists( 'woocommerce_output_product_data_tabs' ) ) {
			return '';
		}

		$GLOBALS['product'] = $product;

		ob_start();
		woocommerce_output_product_data_tabs();
		$html = (string) ob_get_clean();

		return '' === trim( $html ) ? '' : '<div class="br-woo-tabs">' . $html . '</div>';
	}

	/**
	 * Sale badge and/or stock status.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function status( array $settings ): string {
		$product = Context::product();

		if ( null === $product ) {
			return '';
		}

		$show = $this->choice( $settings, 'show', array( 'both', 'sale', 'stock' ), 'both' );
		$html = '';

		if ( 'stock' !== $show && $product->is_on_sale() ) {
			$label   = $this->str( $settings, 'saleText', __( 'Sale', 'breefer' ) );
			$percent = $this->bool( $settings, 'showPercent' ) ? $this->discount_percent( $product ) : 0;

			if ( $percent > 0 ) {
				$label = '-' . $percent . '%';
			}

			$html .= '<span class="br-badge is-sale">' . esc_html( $label ) . '</span>';
		}

		if ( 'sale' !== $show ) {
			$status = $product->get_stock_status();
			$qty    = $product->get_stock_quantity();
			$low    = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

			if ( 'outofstock' === $status ) {
				$html .= '<span class="br-badge is-out">' . esc_html__( 'Out of stock', 'breefer' ) . '</span>';
			} elseif ( 'onbackorder' === $status ) {
				$html .= '<span class="br-badge is-backorder">' . esc_html__( 'On backorder', 'breefer' ) . '</span>';
			} elseif ( $product->managing_stock() && null !== $qty && $qty <= $low ) {
				$html .= '<span class="br-badge is-low">' . esc_html(
					sprintf(
						/* translators: %d: units left. */
						_n( 'Only %d left', 'Only %d left', (int) $qty, 'breefer' ),
						(int) $qty
					)
				) . '</span>';
			} else {
				$html .= '<span class="br-badge is-in">' . esc_html__( 'In stock', 'breefer' ) . '</span>';
			}
		}

		return '' === $html ? '' : '<div class="br-badges">' . $html . '</div>';
	}

	/**
	 * Discount percentage for simple and variable products.
	 *
	 * @param \WC_Product $product Product.
	 * @return int
	 */
	private function discount_percent( \WC_Product $product ): int {
		if ( $product->is_type( 'variable' ) && $product instanceof \WC_Product_Variable ) {
			$regular = (float) $product->get_variation_regular_price( 'max' );
			$sale    = (float) $product->get_variation_sale_price( 'min' );
		} else {
			$regular = (float) $product->get_regular_price();
			$sale    = (float) $product->get_sale_price();
		}

		if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
			return 0;
		}

		return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
	}

	/**
	 * Related, upsell or cross-sell products.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function related( array $settings ): string {
		$product = Context::product();
		$source  = $this->choice( $settings, 'source', array( 'related', 'upsell', 'crosssell' ), 'related' );
		$limit   = $this->int( $settings, 'limit', 1, 24, 4 );
		$columns = $this->int( $settings, 'columns', 1, 6, 4 );
		$ids     = array();

		if ( 'crosssell' === $source && function_exists( 'WC' ) && null !== WC()->cart && ! Context::is_preview() ) {
			$ids = array_map( 'intval', WC()->cart->get_cross_sells() );
		} elseif ( null !== $product ) {
			if ( 'upsell' === $source ) {
				$ids = array_map( 'intval', $product->get_upsell_ids() );
			} elseif ( 'crosssell' === $source ) {
				$ids = array_map( 'intval', $product->get_cross_sell_ids() );
			} else {
				$ids = array_map( 'intval', wc_get_related_products( $product->get_id(), $limit ) );
			}
		}

		$ids = array_slice( array_filter( $ids ), 0, $limit );

		if ( array() === $ids ) {
			return Context::is_preview() ? $this->placeholder( __( 'Related products', 'breefer' ), __( 'Shown when the product has matches.', 'breefer' ) ) : '';
		}

		$html = do_shortcode( '[products ids="' . implode( ',', $ids ) . '" columns="' . $columns . '" orderby="post__in"]' );
		$head = $this->str( $settings, 'heading', __( 'You may also like', 'breefer' ) );

		return '<section class="br-woo-related br-woo-products br-woo-columns-' . $columns . '">'
			. ( '' !== $head ? '<h2 class="br-related-title">' . esc_html( $head ) . '</h2>' : '' )
			. $html . '</section>';
	}

	/**
	 * Result count and sorting dropdown for shop archives.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function ordering( array $settings ): string {
		if ( Context::is_preview() || ! function_exists( 'woocommerce_catalog_ordering' ) ) {
			return $this->placeholder( __( 'Result count & sorting', 'breefer' ), __( 'Appears on shop and category archives.', 'breefer' ) );
		}

		ob_start();

		if ( ! $this->bool( $settings, 'hideCount' ) ) {
			woocommerce_result_count();
		}

		if ( ! $this->bool( $settings, 'hideSorting' ) ) {
			woocommerce_catalog_ordering();
		}

		$html = (string) ob_get_clean();

		return '' === trim( $html ) ? '' : '<div class="br-woo-ordering">' . $html . '</div>';
	}

	/**
	 * Sticky add-to-cart bar for single products.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function sticky( array $settings ): string {
		if ( ! (bool) Options::get( 'woocommerce.sticky_cart', true ) ) {
			return '';
		}

		$product = Context::product();

		if ( null === $product ) {
			return '';
		}

		if ( Context::is_preview() ) {
			return $this->placeholder( __( 'Sticky add-to-cart bar', 'breefer' ), __( 'Slides in when the visitor scrolls past the add to cart button.', 'breefer' ) );
		}

		$simple = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
		$label  = $simple
			? $this->str( $settings, 'buttonText', __( 'Add to cart', 'breefer' ) )
			: $this->str( $settings, 'selectText', __( 'Select options', 'breefer' ) );

		$vars = $this->vars(
			array(
				'--br-sticky-accent' => $this->color( $settings, 'accent' ),
				'--br-sticky-bg'     => $this->color( $settings, 'barBackground' ),
			)
		);

		return sprintf(
			'<div class="br-sticky-cart is-%1$s" data-breefer-sticky data-simple="%2$d" hidden%3$s><div class="br-sticky-cart-inner">%4$s<div class="br-sticky-cart-info"><strong>%5$s</strong><span>%6$s</span></div><button type="button" class="br-w-btn br-sticky-cart-button">%7$s</button></div></div>',
			esc_attr( $this->choice( $settings, 'position', array( 'bottom', 'top' ), 'bottom' ) ),
			$simple ? 1 : 0,
			$vars,
			wp_kses_post( $product->get_image( 'thumbnail', array( 'class' => 'br-sticky-cart-thumb' ) ) ),
			esc_html( $product->get_name() ),
			wp_kses_post( $product->get_price_html() ),
			esc_html( $label )
		);
	}
}
