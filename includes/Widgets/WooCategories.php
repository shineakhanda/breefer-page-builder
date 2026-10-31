<?php
/**
 * WooCommerce product categories widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Compatibility\WooCommerce as WooCompat;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the product category list.
 */
final class WooCategories extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'woocategories';
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
		unset( $settings, $children_html, $node_id );

		if ( ! WooCompat::is_active() || ! taxonomy_exists( 'product_cat' ) ) {
			return '';
		}

		WooCompat::enqueue_assets();

		$html = wp_list_categories(
			array(
				'taxonomy'     => 'product_cat',
				'title_li'     => '',
				'hide_empty'   => false,
				'show_count'   => true,
				'hierarchical' => true,
				'echo'         => false,
			)
		);

		if ( ! is_string( $html ) || '' === trim( $html ) ) {
			return '';
		}

		return '<div class="br-woo-categories"><ul class="product-categories">' . $html . '</ul></div>';
	}
}
