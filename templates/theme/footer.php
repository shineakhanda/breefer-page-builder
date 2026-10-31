<?php
/**
 * Breefer footer region (classic themes).
 *
 * @package Breefer
 *
 * @var int $breefer_region_id Footer template id.
 */

defined( 'ABSPATH' ) || exit;

$breefer_renderer = \Breefer\Plugin::instance()->get( 'renderer' );
?>
<footer class="breefer-theme-footer">
	<?php
	if ( $breefer_renderer instanceof \Breefer\Rendering\Renderer ) {
		echo $breefer_renderer->render_post( (int) $breefer_region_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its output.
	}
	?>
</footer>
<?php wp_footer(); ?>
</body>
</html>
