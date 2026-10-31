<?php
/**
 * Render context (current post / product).
 *
 * @package Breefer
 */

namespace Breefer\Utils;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves "the current post" and "the current product" for dynamic widgets.
 *
 * On the frontend this follows the main query. In the editor preview an
 * override supplies a sample entry so dynamic widgets show real data.
 */
final class Context {

	/**
	 * Preview post override.
	 */
	private static int $post_override = 0;

	/**
	 * Preview product override.
	 */
	private static int $product_override = 0;

	/**
	 * Whether the render is an editor preview.
	 */
	private static bool $preview = false;

	/**
	 * Id of the Breefer document currently being rendered.
	 */
	private static int $document = 0;

	/**
	 * Enter preview mode with sample entries.
	 *
	 * @param int $post_id    Sample post id.
	 * @param int $product_id Sample product id.
	 * @return void
	 */
	public static function begin_preview( int $post_id, int $product_id ): void {
		self::$preview          = true;
		self::$post_override    = $post_id;
		self::$product_override = $product_id;

		if ( $post_id > 0 ) {
			$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $GLOBALS['post'] );
		}

		if ( $product_id > 0 && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );

			if ( $product instanceof \WC_Product ) {
				$GLOBALS['product'] = $product;
			}
		}
	}

	/**
	 * Leave preview mode.
	 *
	 * @return void
	 */
	public static function end_preview(): void {
		self::$preview          = false;
		self::$post_override    = 0;
		self::$product_override = 0;

		wp_reset_postdata();
	}

	/**
	 * Set the document being rendered and return the previous one.
	 *
	 * @param int $id Document id.
	 * @return int Previous document id.
	 */
	public static function set_document( int $id ): int {
		$previous      = self::$document;
		self::$document = $id;

		return $previous;
	}

	/**
	 * Id of the document being rendered.
	 *
	 * @return int
	 */
	public static function document(): int {
		return self::$document;
	}

	/**
	 * Whether the current render is an editor preview.
	 *
	 * @return bool
	 */
	public static function is_preview(): bool {
		return self::$preview;
	}

	/**
	 * The current post, if any.
	 *
	 * @return WP_Post|null
	 */
	public static function post(): ?WP_Post {
		if ( self::$post_override > 0 ) {
			$post = get_post( self::$post_override );

			return $post instanceof WP_Post ? $post : null;
		}

		if ( is_singular() ) {
			$post = get_queried_object();

			if ( $post instanceof WP_Post ) {
				return $post;
			}
		}

		$post = get_post();

		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * The current WooCommerce product, if any.
	 *
	 * @return \WC_Product|null
	 */
	public static function product(): ?\WC_Product {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$id = 0;

		if ( self::$product_override > 0 ) {
			$id = self::$product_override;
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$id = (int) get_queried_object_id();
		} elseif ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product ) {
			return $GLOBALS['product'];
		} elseif ( is_singular() ) {
			$id = (int) get_the_ID();
		}

		if ( $id <= 0 ) {
			return null;
		}

		$product = wc_get_product( $id );

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Find sample entries for the editor preview.
	 *
	 * @return array{post: int, product: int}
	 */
	public static function sample_ids(): array {
		$post = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);

		if ( array() === $post ) {
			$post = get_posts(
				array(
					'post_type'        => 'page',
					'post_status'      => 'publish',
					'numberposts'      => 1,
					'fields'           => 'ids',
					'suppress_filters' => true,
					'no_found_rows'    => true,
				)
			);
		}

		$product = array();

		if ( post_type_exists( 'product' ) ) {
			$product = get_posts(
				array(
					'post_type'        => 'product',
					'post_status'      => 'publish',
					'numberposts'      => 1,
					'fields'           => 'ids',
					'suppress_filters' => true,
					'no_found_rows'    => true,
				)
			);
		}

		return array(
			'post'    => isset( $post[0] ) ? (int) $post[0] : 0,
			'product' => isset( $product[0] ) ? (int) $product[0] : 0,
		);
	}
}
