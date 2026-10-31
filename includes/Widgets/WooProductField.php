<?php
/**
 * WooCommerce dynamic product field widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Compatibility\WooCommerce as WooCompat;
use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Pulls a live field from the current product, for single-product templates.
 */
final class WooProductField extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'woofield';
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

		$product = Context::product();

		if ( null === $product ) {
			return '';
		}

		WooCompat::enqueue_assets();

		$field = sanitize_key( $this->str( $settings, 'field', 'title' ) );

		switch ( $field ) {
			case 'price':
				return '<div class="br-product-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';

			case 'image':
				return '<div class="br-product-image">' . $product->get_image( 'large' ) . '</div>';

			case 'short_description':
				$excerpt = $product->get_short_description();

				return '' !== $excerpt ? '<div class="br-product-excerpt">' . wp_kses_post( wpautop( $excerpt ) ) . '</div>' : '';

			case 'description':
				$description = $product->get_description();

				return '' !== $description ? '<div class="br-product-description">' . wp_kses_post( wpautop( $description ) ) . '</div>' : '';

			case 'sku':
				$sku = $product->get_sku();

				return '' !== $sku ? '<div class="br-product-sku">' . esc_html( $sku ) . '</div>' : '';

			case 'rating':
				return function_exists( 'wc_get_rating_html' )
					? '<div class="br-product-rating">' . wp_kses_post( wc_get_rating_html( $product->get_average_rating() ) ) . '</div>'
					: '';

			case 'add_to_cart':
				$GLOBALS['product'] = $product;

				ob_start();

				if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
					woocommerce_template_single_add_to_cart();
				}

				return '<div class="br-product-cart">' . (string) ob_get_clean() . '</div>';

			case 'gallery':
				$ids = $product->get_gallery_image_ids();

				if ( ! is_array( $ids ) || array() === $ids ) {
					return '';
				}

				$html = '<div class="br-product-gallery">';

				foreach ( $ids as $attachment_id ) {
					$html .= wp_get_attachment_image( (int) $attachment_id, 'large' );
				}

				return $html . '</div>';

			case 'meta':
				$terms = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );

				return is_array( $terms ) && array() !== $terms
					? '<div class="br-product-meta">' . esc_html( implode( ', ', $terms ) ) . '</div>'
					: '';

			case 'images':
				// The full WooCommerce gallery: slider, zoom and lightbox.
				if ( ! function_exists( 'woocommerce_show_product_images' ) || Context::is_preview() ) {
					return '<div class="br-product-image">' . $product->get_image( 'large' ) . '</div>';
				}

				$GLOBALS['product'] = $product;

				ob_start();
				woocommerce_show_product_images();

				return '<div class="br-product-images">' . (string) ob_get_clean() . '</div>';

			case 'attributes':
				if ( ! function_exists( 'wc_display_product_attributes' ) ) {
					return '';
				}

				ob_start();
				wc_display_product_attributes( $product );
				$table = (string) ob_get_clean();

				return '' !== trim( $table ) ? '<div class="br-product-attributes">' . $table . '</div>' : '';

			case 'tags':
				$tags = wc_get_product_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) );

				return is_array( $tags ) && array() !== $tags
					? '<div class="br-product-meta">' . esc_html( implode( ', ', $tags ) ) . '</div>'
					: '';

			case 'rating_count':
				$count = (int) $product->get_review_count();

				return '<div class="br-product-rating-count">' . esc_html(
					sprintf(
						/* translators: %d: review count. */
						_n( '%d review', '%d reviews', $count, 'breefer' ),
						$count
					)
				) . '</div>';

			case 'weight':
				$weight = $product->get_weight();

				return '' !== (string) $weight && function_exists( 'wc_format_weight' )
					? '<div class="br-product-weight">' . esc_html( wc_format_weight( $weight ) ) . '</div>'
					: '';

			case 'title':
			default:
				$tag = $this->choice( $settings, 'tag', array( 'h1', 'h2', 'h3', 'h4', 'p', 'div' ), 'h1' );

				return '<' . $tag . ' class="br-product-title">' . esc_html( $product->get_name() ) . '</' . $tag . '>';
		}
	}
}
