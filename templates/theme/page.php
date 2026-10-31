<?php
/**
 * Theme-builder body template.
 *
 * Used for single posts and pages, archives, search, 404 and the WooCommerce
 * shop, product, cart, checkout and account pages when a Breefer template is
 * assigned to that role.
 *
 * @package Breefer
 */

defined( 'ABSPATH' ) || exit;

$breefer_template_id = isset( $GLOBALS['breefer_template_id'] ) ? (int) $GLOBALS['breefer_template_id'] : 0;
$breefer_type        = isset( $GLOBALS['breefer_template_type'] ) ? (string) $GLOBALS['breefer_template_type'] : '';
$breefer_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
$breefer_is_woo      = in_array( $breefer_type, array( 'single', 'shop', 'cart', 'checkout', 'account' ), true );

if ( 'single' === $breefer_type && function_exists( 'wc_get_product' ) ) {
	$breefer_product = wc_get_product( get_queried_object_id() );

	if ( $breefer_product instanceof \WC_Product ) {
		$GLOBALS['product'] = $breefer_product;
	}
}

if ( 'notfound' === $breefer_type ) {
	status_header( 404 );
	nocache_headers();
}

if ( $breefer_block_theme ) {
	// Block themes have no header.php, so this file produces the whole document.
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php
	wp_body_open();
	echo '<div class="wp-site-blocks">';
	echo do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	get_header();
}
?>
<div class="breefer-theme-wrap<?php echo $breefer_is_woo ? ' woocommerce' : ''; ?>">
	<main id="breefer-main" class="breefer-theme-main">
		<?php
		if ( in_array( $breefer_type, array( 'single', 'shop' ), true ) && function_exists( 'wc_print_notices' ) ) {
			wc_print_notices();
		}

		if ( $breefer_template_id > 0 ) {
			$breefer_renderer = \Breefer\Plugin::instance()->get( 'renderer' );

			if ( $breefer_renderer instanceof \Breefer\Rendering\Renderer ) {
				echo $breefer_renderer->render_post( $breefer_template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its output.
			}
		}
		?>
	</main>
</div>
<?php
if ( $breefer_block_theme ) {
	echo do_blocks( '<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
	wp_footer();
	echo '</body></html>';
} else {
	get_footer();
}
