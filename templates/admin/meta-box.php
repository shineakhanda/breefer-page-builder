<?php
/**
 * Breefer meta box.
 *
 * @package Breefer
 *
 * @var array<string, mixed> $args Template data.
 */

defined( 'ABSPATH' ) || exit;

$breefer_post        = isset( $args['post'] ) ? $args['post'] : null;
$breefer_enabled     = ! empty( $args['enabled'] );
$breefer_is_template = ! empty( $args['is_template'] );
$breefer_editor_url  = isset( $args['editor_url'] ) ? (string) $args['editor_url'] : '';
$breefer_nonce       = isset( $args['nonce'] ) ? (string) $args['nonce'] : '';
$breefer_id          = $breefer_post instanceof WP_Post ? $breefer_post->ID : 0;
?>
<div class="breefer-metabox">
	<?php wp_nonce_field( 'breefer_meta', 'breefer_meta_nonce' ); ?>

	<p class="breefer-metabox-intro">
		<?php esc_html_e( 'Design this page visually. Breefer renders static HTML with no page-builder runtime on the frontend.', 'breefer' ); ?>
	</p>

	<p>
		<a class="button button-primary" href="<?php echo esc_url( $breefer_editor_url ); ?>">
			<?php esc_html_e( 'Open Breefer editor', 'breefer' ); ?>
		</a>
	</p>

	<?php if ( $breefer_is_template ) : ?>
		<p class="description">
			<?php esc_html_e( 'Insert this template anywhere with:', 'breefer' ); ?><br>
			<code>[breefer id="<?php echo esc_html( (string) $breefer_id ); ?>"]</code>
		</p>

		<?php
		$breefer_type       = isset( $args['woo_type'] ) ? (string) $args['woo_type'] : 'none';
		$breefer_types      = isset( $args['types'] ) && is_array( $args['types'] ) ? $args['types'] : array();
		$breefer_conditions = isset( $args['conditions'] ) && is_array( $args['conditions'] ) ? $args['conditions'] : array();
		$breefer_condition  = isset( $args['condition'] ) ? (string) $args['condition'] : 'all';
		?>
		<p>
			<label for="breefer-woo-type"><strong><?php esc_html_e( 'Template type', 'breefer' ); ?></strong></label><br>
			<select name="breefer_woo_type" id="breefer-woo-type" class="widefat">
				<option value="none" <?php selected( $breefer_type, 'none' ); ?>><?php esc_html_e( 'Reusable block (shortcode only)', 'breefer' ); ?></option>
				<?php foreach ( $breefer_types as $breefer_slug => $breefer_info ) : ?>
					<option value="<?php echo esc_attr( (string) $breefer_slug ); ?>" <?php selected( $breefer_type, (string) $breefer_slug ); ?>>
						<?php echo esc_html( (string) ( $breefer_info['label'] ?? $breefer_slug ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="breefer-condition"><strong><?php esc_html_e( 'Display on (headers & footers)', 'breefer' ); ?></strong></label><br>
			<select name="breefer_condition" id="breefer-condition" class="widefat">
				<?php foreach ( $breefer_conditions as $breefer_slug => $breefer_label ) : ?>
					<option value="<?php echo esc_attr( (string) $breefer_slug ); ?>" <?php selected( $breefer_condition, (string) $breefer_slug ); ?>><?php echo esc_html( (string) $breefer_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description">
			<?php esc_html_e( 'The most specific, most recently edited template of each type is used on the frontend.', 'breefer' ); ?>
		</p>
	<?php else : ?>
		<p>
			<label>
				<input type="checkbox" name="breefer_enabled" value="1" <?php checked( $breefer_enabled ); ?>>
				<?php esc_html_e( 'Render this page with Breefer', 'breefer' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'While enabled, the builder output replaces the post content on the frontend. Your original content is kept and returns when you disable it.', 'breefer' ); ?>
		</p>
	<?php endif; ?>
</div>
