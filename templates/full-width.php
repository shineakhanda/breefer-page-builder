<?php
/**
 * Full-width layout: theme header and footer, unconstrained Breefer content.
 *
 * @package Breefer
 */

defined( 'ABSPATH' ) || exit;

$breefer_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
$breefer_plugin      = \Breefer\Plugin::instance();
$breefer_renderer    = $breefer_plugin->get( 'renderer' );

if ( $breefer_block_theme ) {
	echo do_blocks( '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	get_header();
}
?>
<main id="breefer-full-width" class="breefer-full-width">
	<?php
	if ( $breefer_renderer instanceof \Breefer\Rendering\Renderer ) {
		echo $breefer_renderer->render_post( (int) get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its output.
	}
	?>
</main>
<?php
if ( $breefer_block_theme ) {
	echo do_blocks( '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	get_footer();
}
