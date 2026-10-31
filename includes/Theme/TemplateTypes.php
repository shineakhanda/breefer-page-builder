<?php
/**
 * Theme builder template types and display conditions.
 *
 * @package Breefer
 */

namespace Breefer\Theme;

use Breefer\Compatibility\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of template roles (header, footer, single, archive, shop, cart ...)
 * and the display conditions that decide where header/footer templates apply.
 */
final class TemplateTypes {

	/**
	 * Post meta holding the template type.
	 *
	 * The key keeps its original "woo" name so templates created before the
	 * theme builder existed (shop, single) continue to work.
	 */
	public const META = '_breefer_woo_type';

	/**
	 * Post meta holding the display condition (headers and footers).
	 */
	public const CONDITION = '_breefer_condition';

	/**
	 * Every template type.
	 *
	 * @return array<string, array{label: string, group: string, conditional: bool, hint: string}>
	 */
	public static function all(): array {
		return array(
			'header'   => array(
				'label'       => __( 'Header', 'breefer' ),
				'group'       => 'theme',
				'conditional' => true,
				'hint'        => __( 'Replaces your theme header.', 'breefer' ),
			),
			'footer'   => array(
				'label'       => __( 'Footer', 'breefer' ),
				'group'       => 'theme',
				'conditional' => true,
				'hint'        => __( 'Replaces your theme footer.', 'breefer' ),
			),
			'post'     => array(
				'label'       => __( 'Single post', 'breefer' ),
				'group'       => 'theme',
				'conditional' => false,
				'hint'        => __( 'Layout for every blog post. Add a Post field widget set to Content.', 'breefer' ),
			),
			'page'     => array(
				'label'       => __( 'Single page', 'breefer' ),
				'group'       => 'theme',
				'conditional' => false,
				'hint'        => __( 'Layout for pages that are not built with Breefer on their own.', 'breefer' ),
			),
			'archive'  => array(
				'label'       => __( 'Archive / blog', 'breefer' ),
				'group'       => 'theme',
				'conditional' => false,
				'hint'        => __( 'Categories, tags, authors, dates and the blog index. Use the Posts grid in "main query" mode.', 'breefer' ),
			),
			'search'   => array(
				'label'       => __( 'Search results', 'breefer' ),
				'group'       => 'theme',
				'conditional' => false,
				'hint'        => __( 'Search results page.', 'breefer' ),
			),
			'notfound' => array(
				'label'       => __( '404 page', 'breefer' ),
				'group'       => 'theme',
				'conditional' => false,
				'hint'        => __( 'Shown when a page cannot be found.', 'breefer' ),
			),
			'single'   => array(
				'label'       => __( 'Single product', 'breefer' ),
				'group'       => 'woocommerce',
				'conditional' => false,
				'hint'        => __( 'Layout for every product page.', 'breefer' ),
			),
			'shop'     => array(
				'label'       => __( 'Shop & product archives', 'breefer' ),
				'group'       => 'woocommerce',
				'conditional' => false,
				'hint'        => __( 'The shop page, product categories and tags.', 'breefer' ),
			),
			'cart'     => array(
				'label'       => __( 'Cart', 'breefer' ),
				'group'       => 'woocommerce',
				'conditional' => false,
				'hint'        => __( 'Add the Cart widget. WooCommerce keeps handling totals and coupons.', 'breefer' ),
			),
			'checkout' => array(
				'label'       => __( 'Checkout', 'breefer' ),
				'group'       => 'woocommerce',
				'conditional' => false,
				'hint'        => __( 'Add the Checkout widget. Payment gateways keep working untouched.', 'breefer' ),
			),
			'account'  => array(
				'label'       => __( 'My account', 'breefer' ),
				'group'       => 'woocommerce',
				'conditional' => false,
				'hint'        => __( 'Add the My account widget for login, orders and addresses.', 'breefer' ),
			),
		);
	}

	/**
	 * Types usable on this site (WooCommerce types need WooCommerce).
	 *
	 * @return array<string, array{label: string, group: string, conditional: bool, hint: string}>
	 */
	public static function available(): array {
		$types = self::all();

		if ( ! WooCommerce::is_active() ) {
			$types = array_filter(
				$types,
				static fn( array $type ): bool => 'woocommerce' !== $type['group']
			);
		}

		return $types;
	}

	/**
	 * Whether a slug is a known type.
	 *
	 * @param string $type Slug.
	 * @return bool
	 */
	public static function exists( string $type ): bool {
		return isset( self::all()[ $type ] );
	}

	/**
	 * Display conditions for headers and footers.
	 *
	 * @return array<string, string>
	 */
	public static function conditions(): array {
		return array(
			'all'      => __( 'Entire site', 'breefer' ),
			'front'    => __( 'Front page', 'breefer' ),
			'posts'    => __( 'Blog posts', 'breefer' ),
			'pages'    => __( 'Pages', 'breefer' ),
			'archives' => __( 'Archives & search', 'breefer' ),
			'woo'      => __( 'WooCommerce pages', 'breefer' ),
			'notwoo'   => __( 'Everything except WooCommerce', 'breefer' ),
		);
	}

	/**
	 * Whether the current request matches a display condition.
	 *
	 * @param string $condition Condition slug.
	 * @return bool
	 */
	public static function matches( string $condition ): bool {
		$is_woo = self::is_woo_request();

		switch ( $condition ) {
			case 'front':
				return is_front_page();
			case 'posts':
				return is_singular( 'post' );
			case 'pages':
				return is_page() && ! $is_woo;
			case 'archives':
				return ( is_archive() || is_search() || is_home() ) && ! $is_woo;
			case 'woo':
				return $is_woo;
			case 'notwoo':
				return ! $is_woo;
			case 'all':
			default:
				return true;
		}
	}

	/**
	 * Whether the request belongs to WooCommerce (shop, product, cart, checkout, account).
	 *
	 * @return bool
	 */
	public static function is_woo_request(): bool {
		if ( ! WooCommerce::is_active() ) {
			return false;
		}

		return ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			|| ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() );
	}

	/**
	 * Template type for the current request (body templates only).
	 *
	 * @return string Empty when no theme-builder role applies.
	 */
	public static function detect(): string {
		if ( is_404() ) {
			return 'notfound';
		}

		if ( WooCommerce::is_active() ) {
			if ( function_exists( 'is_product' ) && is_product() ) {
				return 'single';
			}

			if ( function_exists( 'is_shop' ) && ( is_shop() || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) ) {
				return 'shop';
			}

			if ( function_exists( 'is_cart' ) && is_cart() ) {
				return 'cart';
			}

			if ( function_exists( 'is_checkout' ) && is_checkout() ) {
				return 'checkout';
			}

			if ( function_exists( 'is_account_page' ) && is_account_page() ) {
				return 'account';
			}
		}

		if ( is_search() ) {
			return 'search';
		}

		if ( is_singular( 'post' ) ) {
			return 'post';
		}

		if ( is_page() ) {
			return 'page';
		}

		if ( is_archive() || is_home() ) {
			return 'archive';
		}

		return '';
	}
}
