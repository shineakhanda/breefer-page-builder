<?php
/**
 * WooCommerce product grid widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Compatibility\WooCommerce as WooCompat;
use Breefer\Utils\Context;
use Breefer\Utils\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Renders WooCommerce's native products loop (so every hook, badge and add-to-cart
 * flow keeps working) with sources, a carousel layout and design-token styling.
 */
final class WooProducts extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'wooproducts';
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

		$limit    = $this->int( $settings, 'limit', 1, 48, 8 );
		$columns  = $this->int( $settings, 'columns', 1, 6, 4 );
		$category = sanitize_title( $this->str( $settings, 'category' ) );
		$source   = $this->choice( $settings, 'source', array( 'latest', 'featured', 'sale', 'best', 'top_rated', 'ids', 'main' ), 'latest' );
		$orderby  = $this->choice( $settings, 'orderby', array( 'date', 'price', 'rating', 'popularity', 'title', 'menu_order', 'rand' ), 'date' );
		$order    = 'ASC' === strtoupper( $this->str( $settings, 'order', 'DESC' ) ) ? 'ASC' : 'DESC';

		// Shop and category templates list the main query with WooCommerce's own loop.
		if ( 'main' === $source && ! Context::is_preview() && function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || is_search() ) ) {
			return $this->main_loop( $settings, $columns );
		}

		if ( 'main' === $source ) {
			$source = 'latest';
		}

		$atts = array(
			'limit'   => (string) $limit,
			'columns' => (string) $columns,
			'orderby' => $orderby,
			'order'   => $order,
		);

		if ( '' !== $category ) {
			$atts['category'] = $category;
		}

		switch ( $source ) {
			case 'featured':
				$atts['visibility'] = 'featured';
				break;
			case 'sale':
				$atts['on_sale'] = 'true';
				break;
			case 'best':
				$atts['best_selling'] = 'true';
				break;
			case 'top_rated':
				$atts['top_rated'] = 'true';
				break;
			case 'ids':
				$ids = array_filter( array_map( 'absint', explode( ',', $this->str( $settings, 'ids' ) ) ) );

				if ( array() === $ids ) {
					return $this->placeholder( __( 'Product grid', 'breefer' ), __( 'Enter product IDs separated by commas.', 'breefer' ) );
				}

				$atts['ids'] = implode( ',', $ids );
				break;
		}

		$shortcode = '[products';

		foreach ( $atts as $key => $value ) {
			$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		$shortcode .= ']';

		// Honour the Breefer toggle for AJAX add-to-cart inside product grids only.
		$force_ajax = (bool) Options::get( 'woocommerce.ajax_add_to_cart', true );
		$filter     = static fn(): string => 'yes';

		if ( $force_ajax ) {
			add_filter( 'pre_option_woocommerce_enable_ajax_add_to_cart', $filter );
		}

		$html = do_shortcode( $shortcode );

		if ( $force_ajax ) {
			remove_filter( 'pre_option_woocommerce_enable_ajax_add_to_cart', $filter );
		}

		if ( '' === trim( $html ) ) {
			return $this->placeholder( __( 'Product grid', 'breefer' ), __( 'No products match these settings yet.', 'breefer' ) );
		}

		return '<div class="' . esc_attr( $this->classes( $settings, $columns ) ) . '"' . $this->style_vars( $settings, $columns ) . '>' . $html . '</div>';
	}

	/**
	 * Render the main query through WooCommerce's loop templates.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param int                  $columns  Columns.
	 * @return string
	 */
	private function main_loop( array $settings, int $columns ): string {
		wc_set_loop_prop( 'columns', $columns );

		ob_start();

		if ( woocommerce_product_loop() ) {
			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();

					do_action( 'woocommerce_shop_loop' );
					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			do_action( 'woocommerce_after_shop_loop' );
		} else {
			do_action( 'woocommerce_no_products_found' );
		}

		wp_reset_postdata();

		return '<div class="' . esc_attr( $this->classes( $settings, $columns ) ) . '"' . $this->style_vars( $settings, $columns ) . '>' . (string) ob_get_clean() . '</div>';
	}

	/**
	 * Wrapper classes.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param int                  $columns  Columns.
	 * @return string
	 */
	private function classes( array $settings, int $columns ): string {
		return 'br-woo-products br-woo-columns-' . $columns
			. ( 'carousel' === $this->str( $settings, 'layout' ) ? ' is-carousel' : '' )
			. ( $this->bool( $settings, 'hoverZoom' ) ? ' has-zoom' : '' )
			. ( $this->bool( $settings, 'hideRating' ) ? ' hide-rating' : '' );
	}

	/**
	 * Wrapper style variables.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param int                  $columns  Columns.
	 * @return string
	 */
	private function style_vars( array $settings, int $columns ): string {
		return $this->vars(
			array(
				'--br-card-bg'     => $this->color( $settings, 'cardBackground' ),
				'--br-card-accent' => $this->color( $settings, 'accent' ),
				'--br-card-radius' => $this->px( $settings, 'cardRadius', 0, 40 ),
				'--br-card-gap'    => $this->px( $settings, 'gap', 0, 80 ),
				'--br-card-cols'   => (string) $columns,
			)
		);
	}
}
