<?php
/**
 * WooCommerce integration.
 *
 * @package Breefer
 */

namespace Breefer\Compatibility;

use Breefer\Documents\Document;
use Breefer\Frontend\FrontendAssets;
use Breefer\Theme\TemplateTypes;
use Breefer\Utils\Options;
use Breefer\Widgets\WooMiniCart;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce assets, cart fragments and theme support for Breefer widgets.
 *
 * Template takeovers (shop, product, cart, checkout, account) live in
 * Theme\ThemeBuilder together with the other template roles.
 */
final class WooCommerce {

	/**
	 * Template meta key storing the template type (shared with the theme builder).
	 */
	public const META = TemplateTypes::META;

	/**
	 * Widgets that need WooCommerce scripts.
	 *
	 * @var array<int, string>
	 */
	public const WIDGETS = array(
		'wooproducts',
		'woofield',
		'woocart',
		'woocategories',
		'woocarttable',
		'woocheckout',
		'wooaccount',
		'wootabs',
		'woostatus',
		'woorelated',
		'wooordering',
		'woosticky',
	);

	/**
	 * Whether WooCommerce is available.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'wc_get_product' );
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 20 );
		add_action( 'after_setup_theme', array( $this, 'theme_support' ), 20 );
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragments' ) );
	}

	/**
	 * Enable the product gallery features the Product images field relies on.
	 *
	 * Themes that already declare support are left alone.
	 *
	 * @return void
	 */
	public function theme_support(): void {
		if ( ! self::is_active() ) {
			return;
		}

		foreach ( array( 'wc-product-gallery-zoom', 'wc-product-gallery-lightbox', 'wc-product-gallery-slider' ) as $feature ) {
			if ( ! current_theme_supports( $feature ) ) {
				add_theme_support( $feature );
			}
		}
	}

	/**
	 * Keep the cart widget's count and subtotal in sync after AJAX add-to-cart.
	 *
	 * @param array<string, string> $fragments Fragments.
	 * @return array<string, string>
	 */
	public function cart_fragments( array $fragments ): array {
		if ( ! (bool) Options::get( 'woocommerce.cart_fragments', true ) || ! function_exists( 'WC' ) || null === WC()->cart ) {
			return $fragments;
		}

		$fragments['span.br-cart-count'] = WooMiniCart::count_html( (int) WC()->cart->get_cart_contents_count() );
		$fragments['span.br-cart-total'] = WooMiniCart::total_html( WC()->cart->get_cart_subtotal() );

		return $fragments;
	}

	/**
	 * Add a body class when a Breefer template drives a WooCommerce page.
	 *
	 * @param array<int, string> $classes Classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( ! empty( $GLOBALS['breefer_template_id'] ) && TemplateTypes::is_woo_request() ) {
			$classes[] = 'breefer-woo-template';
		}

		return $classes;
	}

	/**
	 * Enqueue frontend assets for Woo-powered Breefer pages.
	 *
	 * @return void
	 */
	public function enqueue_frontend(): void {
		if ( ! self::is_active() ) {
			return;
		}

		if ( empty( $GLOBALS['breefer_template_id'] ) && ! $this->page_uses_woo_widget() ) {
			return;
		}

		self::enqueue_assets();
	}

	/**
	 * Enqueue WooCommerce and Breefer assets.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( ! self::is_active() ) {
			return;
		}

		FrontendAssets::enqueue();

		foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen' ) as $style ) {
			if ( wp_style_is( $style, 'registered' ) ) {
				wp_enqueue_style( $style );
			}
		}

		$scripts = array( 'wc-add-to-cart', 'wc-single-product' );

		if ( (bool) Options::get( 'woocommerce.cart_fragments', true ) ) {
			$scripts[] = 'wc-cart-fragments';
		}

		foreach ( $scripts as $script ) {
			if ( wp_script_is( $script, 'registered' ) ) {
				wp_enqueue_script( $script );
			}
		}

		$type = isset( $GLOBALS['breefer_template_type'] ) ? (string) $GLOBALS['breefer_template_type'] : '';

		if ( 'single' === $type && wp_script_is( 'wc-add-to-cart-variation', 'registered' ) ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}
	}

	/**
	 * Whether the current singular page contains Woo widgets.
	 *
	 * @return bool
	 */
	private function page_uses_woo_widget(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();

		if ( null === $post ) {
			return false;
		}

		if ( has_block( 'breefer/template', $post ) || has_shortcode( (string) $post->post_content, 'breefer' ) ) {
			return true;
		}

		if ( ! Document::is_enabled( $post->ID ) ) {
			return false;
		}

		return Document::contains_any_type( Document::get( $post->ID ), self::WIDGETS );
	}
}
