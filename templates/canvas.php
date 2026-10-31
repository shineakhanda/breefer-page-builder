<?php
/**
 * Blank canvas layout.
 *
 * @package Breefer
 */

defined( 'ABSPATH' ) || exit;

$breefer_plugin   = \Breefer\Plugin::instance();
$breefer_renderer = $breefer_plugin->get( 'renderer' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'breefer-canvas-page' ); ?>>
<?php wp_body_open(); ?>
<main id="breefer-canvas-content" class="breefer-canvas-page-content">
	<?php
	if ( $breefer_renderer instanceof \Breefer\Rendering\Renderer ) {
		echo $breefer_renderer->render_post( (int) get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its output.
	}
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
