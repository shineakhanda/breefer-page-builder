<?php
/**
 * Theme builder screen.
 *
 * @package Breefer
 *
 * @var array<string, mixed> $args Template data.
 */

defined( 'ABSPATH' ) || exit;

$breefer_types   = isset( $args['types'] ) && is_array( $args['types'] ) ? $args['types'] : array();
$breefer_by_type = isset( $args['by_type'] ) && is_array( $args['by_type'] ) ? $args['by_type'] : array();
$breefer_add_url = isset( $args['add_url'] ) ? (string) $args['add_url'] : '#';
$breefer_groups  = array(
	'theme'       => __( 'Site templates', 'breefer' ),
	'woocommerce' => __( 'WooCommerce templates', 'breefer' ),
);
?>
<div class="breefer-admin">
	<div class="breefer-wrap">
		<section class="breefer-hero">
			<div class="breefer-hero-copy">
				<span class="breefer-eyebrow"><?php esc_html_e( 'Theme Builder', 'breefer' ); ?></span>
				<h1><?php esc_html_e( 'Design every part of your site.', 'breefer' ); ?></h1>
				<p><?php esc_html_e( 'Assign a Breefer template to each role. The most specific, most recently edited template wins. Unassigned roles keep using your theme.', 'breefer' ); ?></p>
			</div>
		</section>

		<?php foreach ( $breefer_groups as $breefer_group => $breefer_group_label ) : ?>
			<?php
			$breefer_rows = array_filter(
				$breefer_types,
				static fn( array $type ): bool => ( $type['group'] ?? '' ) === $breefer_group
			);

			if ( array() === $breefer_rows ) {
				continue;
			}
			?>
			<section class="breefer-card" style="margin-top:20px">
				<header class="breefer-card-head">
					<div>
						<h2><?php echo esc_html( $breefer_group_label ); ?></h2>
					</div>
				</header>

				<?php foreach ( $breefer_rows as $breefer_slug => $breefer_type ) : ?>
					<?php $breefer_items = isset( $breefer_by_type[ $breefer_slug ] ) && is_array( $breefer_by_type[ $breefer_slug ] ) ? $breefer_by_type[ $breefer_slug ] : array(); ?>
					<div class="breefer-field">
						<div class="breefer-field-text">
							<label><?php echo esc_html( (string) $breefer_type['label'] ); ?></label>
							<p class="breefer-help"><?php echo esc_html( (string) $breefer_type['hint'] ); ?></p>
							<?php if ( array() !== $breefer_items ) : ?>
								<ul style="margin:8px 0 0;padding:0;list-style:none">
									<?php foreach ( $breefer_items as $breefer_item ) : ?>
										<li style="margin:2px 0">
											<a href="<?php echo esc_url( (string) $breefer_item['edit_url'] ); ?>"><?php echo esc_html( (string) $breefer_item['title'] ); ?></a>
											<?php if ( ! empty( $breefer_type['conditional'] ) && '' !== (string) $breefer_item['condition'] ) : ?>
												<small style="opacity:.7"> · <?php echo esc_html( (string) $breefer_item['condition'] ); ?></small>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
						<div class="breefer-field-control">
							<a class="breefer-btn is-primary" href="<?php echo esc_url( $breefer_add_url . rawurlencode( (string) $breefer_slug ) ); ?>">
								<?php esc_html_e( 'Add new', 'breefer' ); ?>
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</section>
		<?php endforeach; ?>
	</div>
</div>
