<?php
/**
 * WooCommerce cart / checkout / account page widgets.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Compatibility\WooCommerce as WooCompat;
use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Embeds the native WooCommerce cart, checkout or My Account experience so a
 * Breefer template can design the surrounding page while WooCommerce keeps full
 * ownership of totals, payment gateways, validation and endpoints.
 */
final class WooPage extends AbstractWidget {

	/**
	 * Widget type => shortcode and label.
	 *
	 * @var array<string, array{shortcode: string, label: string, page: string}>
	 */
	private const PAGES = array(
		'woocarttable' => array(
			'shortcode' => 'woocommerce_cart',
			'label'     => 'Cart',
			'page'      => 'cart',
		),
		'woocheckout'  => array(
			'shortcode' => 'woocommerce_checkout',
			'label'     => 'Checkout',
			'page'      => 'checkout',
		),
		'wooaccount'   => array(
			'shortcode' => 'woocommerce_my_account',
			'label'     => 'My account',
			'page'      => 'myaccount',
		),
	);

	/**
	 * Widget type handled by this instance.
	 */
	private string $page;

	/**
	 * Constructor.
	 *
	 * @param string $page woocarttable|woocheckout|wooaccount.
	 */
	public function __construct( string $page = 'woocarttable' ) {
		$this->page = isset( self::PAGES[ $page ] ) ? $page : 'woocarttable';
	}

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return $this->page;
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

		if ( ! WooCompat::is_active() ) {
			return '';
		}

		$meta = self::PAGES[ $this->page ];

		// These flows need a live session and cart, so the editor shows a stand-in.
		if ( Context::is_preview() ) {
			return $this->placeholder(
				/* translators: %s: page name (Cart, Checkout, My account). */
				sprintf( __( 'WooCommerce %s', 'breefer' ), $meta['label'] ),
				__( 'The live WooCommerce page renders here on your site.', 'breefer' )
			);
		}

		WooCompat::enqueue_assets();

		return '<div class="br-woo-page woocommerce">' . $this->page_markup( $meta ) . '</div>';
	}

	/**
	 * Render the assigned WooCommerce page.
	 *
	 * The page's own content is used so stores on the Cart/Checkout blocks and
	 * stores on the classic shortcodes both work. Falls back to the shortcode.
	 *
	 * @param array{shortcode: string, label: string, page: string} $meta Page meta.
	 * @return string
	 */
	private function page_markup( array $meta ): string {
		$page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( $meta['page'] ) : 0;
		$content = $page_id > 0 ? (string) get_post_field( 'post_content', $page_id ) : '';

		if ( '' === trim( $content ) ) {
			$content = '[' . $meta['shortcode'] . ']';
		}

		return do_shortcode( do_blocks( $content ) );
	}
}
