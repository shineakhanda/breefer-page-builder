<?php
/**
 * Breefer header region (classic themes).
 *
 * Replaces the theme's header.php: it opens the document, so the theme's own
 * header is discarded by ThemeBuilder after this file runs.
 *
 * @package Breefer
 *
 * @var int $breefer_region_id Header template id.
 */

defined( 'ABSPATH' ) || exit;

$breefer_renderer = \Breefer\Plugin::instance()->get( 'renderer' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="breefer-theme-header">
	<?php
	if ( $breefer_renderer instanceof \Breefer\Rendering\Renderer ) {
		echo $breefer_renderer->render_post( (int) $breefer_region_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its output.
	}
	?>
</header>
