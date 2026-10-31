<?php
/**
 * Settings template.
 *
 * @package Breefer
 *
 * @var array<string, mixed> $args Template data.
 */

defined( 'ABSPATH' ) || exit;

$breefer_settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$breefer_palettes = isset( $args['palettes'] ) && is_array( $args['palettes'] ) ? $args['palettes'] : array();
$breefer_roles    = isset( $args['roles'] ) && is_array( $args['roles'] ) ? $args['roles'] : array();
$breefer_group    = isset( $args['group'] ) ? (string) $args['group'] : 'breefer_settings_group';
$breefer_design   = isset( $args['design'] ) && is_array( $args['design'] ) ? $args['design'] : array();
$breefer_tokens   = isset( $args['tokens'] ) && is_array( $args['tokens'] ) ? $args['tokens'] : array();
$breefer_fonts    = isset( $args['fonts'] ) && is_array( $args['fonts'] ) ? $args['fonts'] : array();
$breefer_woo      = isset( $breefer_settings['woocommerce'] ) && is_array( $breefer_settings['woocommerce'] ) ? $breefer_settings['woocommerce'] : array();

$breefer_active_palette = isset( $breefer_settings['appearance']['palette'] ) ? (string) $breefer_settings['appearance']['palette'] : 'violet';
$breefer_editor         = isset( $breefer_settings['editor'] ) && is_array( $breefer_settings['editor'] ) ? $breefer_settings['editor'] : array();
$breefer_performance    = isset( $breefer_settings['performance'] ) && is_array( $breefer_settings['performance'] ) ? $breefer_settings['performance'] : array();
$breefer_active_role    = isset( $breefer_settings['access']['role'] ) ? (string) $breefer_settings['access']['role'] : 'administrator';

$breefer_sections = array(
	'appearance'  => __( 'Appearance', 'breefer' ),
	'design'      => __( 'Design system', 'breefer' ),
	'woocommerce' => __( 'WooCommerce', 'breefer' ),
	'editor'      => __( 'Editor', 'breefer' ),
	'performance' => __( 'Performance', 'breefer' ),
	'access'      => __( 'Access', 'breefer' ),
);
?>
<div class="breefer-admin">
	<div class="breefer-wrap breefer-settings">

		<header class="breefer-page-head">
			<div>
				<span class="breefer-eyebrow"><?php esc_html_e( 'Breefer', 'breefer' ); ?></span>
				<h1><?php esc_html_e( 'Settings', 'breefer' ); ?></h1>
				<p><?php esc_html_e( 'Editor defaults, performance posture, and who gets to build with Breefer.', 'breefer' ); ?></p>
			</div>
			<span class="breefer-pill is-soft">
				<?php
				printf(
					/* translators: %s: plugin version. */
					esc_html__( 'v%s', 'breefer' ),
					esc_html( isset( $args['version'] ) ? (string) $args['version'] : '' )
				);
				?>
			</span>
		</header>

		<?php settings_errors(); ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="breefer-settings-form">
			<?php settings_fields( $breefer_group ); ?>

			<div class="breefer-settings-layout">
				<nav class="breefer-settings-nav" data-breefer-settings-nav aria-label="<?php esc_attr_e( 'Settings sections', 'breefer' ); ?>">
					<?php foreach ( $breefer_sections as $breefer_id => $breefer_label ) : ?>
						<a href="#<?php echo esc_attr( $breefer_id ); ?>"<?php echo 'appearance' === $breefer_id ? ' class="is-active"' : ''; ?>>
							<?php echo esc_html( $breefer_label ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<div class="breefer-settings-body">

					<section class="breefer-card" id="appearance">
						<header class="breefer-card-head">
							<div>
								<h2><?php esc_html_e( 'Appearance', 'breefer' ); ?></h2>
								<p class="breefer-help"><?php esc_html_e( 'Palettes are shared by the admin screens and the React editor.', 'breefer' ); ?></p>
							</div>
						</header>

						<div class="breefer-palettes">
							<?php foreach ( $breefer_palettes as $breefer_slug => $breefer_palette ) : ?>
								<?php
								$breefer_slug      = (string) $breefer_slug;
								$breefer_is_active = $breefer_slug === $breefer_active_palette;
								$breefer_sidebar   = isset( $breefer_palette['sidebar'] ) ? (string) $breefer_palette['sidebar'] : '#4b2d8a';
								$breefer_sidebar2  = isset( $breefer_palette['sidebar-2'] ) ? (string) $breefer_palette['sidebar-2'] : '#352066';
								$breefer_accent    = isset( $breefer_palette['accent'] ) ? (string) $breefer_palette['accent'] : '#7c5cfc';
								?>
								<label class="breefer-palette<?php echo $breefer_is_active ? ' is-selected' : ''; ?>">
									<input
										type="radio"
										name="breefer_settings[appearance][palette]"
										value="<?php echo esc_attr( $breefer_slug ); ?>"
										<?php checked( $breefer_is_active ); ?>
									>
									<span class="breefer-palette-swatch" style="background:linear-gradient(160deg, <?php echo esc_attr( $breefer_sidebar ); ?>, <?php echo esc_attr( $breefer_sidebar2 ); ?>);">
										<i style="background:<?php echo esc_attr( $breefer_accent ); ?>;"></i>
									</span>
									<span class="breefer-palette-meta">
										<strong><?php echo esc_html( isset( $breefer_palette['label'] ) ? (string) $breefer_palette['label'] : $breefer_slug ); ?></strong>
										<small>
											<?php
											echo esc_html(
												isset( $breefer_palette['mode'] ) && 'dark' === $breefer_palette['mode']
													? __( 'Dark', 'breefer' )
													: __( 'Light', 'breefer' )
											);
											?>
										</small>
									</span>
									<span class="breefer-palette-check" aria-hidden="true"></span>
								</label>
							<?php endforeach; ?>
						</div>
					</section>

					<section class="breefer-card" id="design">
						<header class="breefer-card-head">
							<div>
								<h2><?php esc_html_e( 'Design system', 'breefer' ); ?></h2>
								<p class="breefer-help"><?php esc_html_e( 'Global colours, type and shape. Pick a global colour on any widget and it follows these values everywhere. No remote fonts are loaded.', 'breefer' ); ?></p>
							</div>
						</header>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label><?php esc_html_e( 'Global colours', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Available as swatches in the builder colour picker.', 'breefer' ); ?></p>
							</div>
							<div class="breefer-field-control" style="display:flex;flex-wrap:wrap;gap:14px">
								<?php foreach ( $breefer_tokens as $breefer_slug => $breefer_token ) : ?>
									<label style="display:grid;gap:4px;font-size:12px;justify-items:start">
										<span><?php echo esc_html( (string) $breefer_token['label'] ); ?></span>
										<input type="color" name="breefer_settings[design][colors][<?php echo esc_attr( (string) $breefer_slug ); ?>]" value="<?php echo esc_attr( (string) ( $breefer_design['colors'][ $breefer_slug ] ?? $breefer_token['default'] ) ); ?>">
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-body-font"><?php esc_html_e( 'Body font', 'breefer' ); ?></label>
							</div>
							<div class="breefer-field-control">
								<select class="breefer-input" id="breefer-body-font" name="breefer_settings[design][typography][body_font]">
									<?php foreach ( $breefer_fonts as $breefer_slug => $breefer_font ) : ?>
										<option value="<?php echo esc_attr( (string) $breefer_slug ); ?>" <?php selected( (string) ( $breefer_design['typography']['body_font'] ?? 'system' ), (string) $breefer_slug ); ?>><?php echo esc_html( (string) $breefer_font['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-heading-font"><?php esc_html_e( 'Heading font', 'breefer' ); ?></label>
							</div>
							<div class="breefer-field-control">
								<select class="breefer-input" id="breefer-heading-font" name="breefer_settings[design][typography][heading_font]">
									<?php foreach ( $breefer_fonts as $breefer_slug => $breefer_font ) : ?>
										<option value="<?php echo esc_attr( (string) $breefer_slug ); ?>" <?php selected( (string) ( $breefer_design['typography']['heading_font'] ?? 'system' ), (string) $breefer_slug ); ?>><?php echo esc_html( (string) $breefer_font['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<?php
						$breefer_numbers = array(
							array( 'typography', 'base_size', __( 'Base font size', 'breefer' ), 'px', 12, 24, 1 ),
							array( 'typography', 'scale', __( 'Heading scale', 'breefer' ), '×', 1.05, 1.6, 0.025 ),
							array( 'typography', 'line_height', __( 'Line height', 'breefer' ), '', 1.1, 2.4, 0.05 ),
							array( 'typography', 'heading_weight', __( 'Heading weight', 'breefer' ), '', 300, 900, 100 ),
							array( 'shape', 'radius', __( 'Corner radius', 'breefer' ), 'px', 0, 48, 1 ),
							array( 'shape', 'container', __( 'Content width', 'breefer' ), 'px', 640, 2000, 10 ),
							array( 'shape', 'space', __( 'Spacing unit', 'breefer' ), 'px', 2, 16, 1 ),
						);

						foreach ( $breefer_numbers as $breefer_number ) :
							list( $breefer_group_key, $breefer_key, $breefer_label, $breefer_unit, $breefer_min, $breefer_max, $breefer_step ) = $breefer_number;
							?>
							<div class="breefer-field">
								<div class="breefer-field-text">
									<label for="breefer-design-<?php echo esc_attr( $breefer_key ); ?>"><?php echo esc_html( $breefer_label ); ?></label>
								</div>
								<div class="breefer-field-control">
									<input
										class="breefer-input is-narrow"
										type="number"
										id="breefer-design-<?php echo esc_attr( $breefer_key ); ?>"
										name="breefer_settings[design][<?php echo esc_attr( $breefer_group_key ); ?>][<?php echo esc_attr( $breefer_key ); ?>]"
										value="<?php echo esc_attr( (string) ( $breefer_design[ $breefer_group_key ][ $breefer_key ] ?? '' ) ); ?>"
										min="<?php echo esc_attr( (string) $breefer_min ); ?>"
										max="<?php echo esc_attr( (string) $breefer_max ); ?>"
										step="<?php echo esc_attr( (string) $breefer_step ); ?>"
									>
									<?php if ( '' !== $breefer_unit ) : ?>
										<span class="breefer-unit"><?php echo esc_html( $breefer_unit ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</section>

					<section class="breefer-card" id="woocommerce">
						<header class="breefer-card-head">
							<div>
								<h2><?php esc_html_e( 'WooCommerce', 'breefer' ); ?></h2>
								<p class="breefer-help"><?php esc_html_e( 'Storefront behaviour for Breefer product widgets. Compatible with HPOS and the Cart/Checkout blocks.', 'breefer' ); ?></p>
							</div>
						</header>

						<?php
						$breefer_woo_toggles = array(
							'ajax_add_to_cart' => array( __( 'AJAX add to cart in product grids', 'breefer' ), __( 'Add products without reloading the page, even if your store setting is off.', 'breefer' ) ),
							'cart_fragments'   => array( __( 'Live cart updates', 'breefer' ), __( 'Keep the cart icon count and subtotal in sync after a product is added.', 'breefer' ) ),
							'sticky_cart'      => array( __( 'Sticky add-to-cart bar', 'breefer' ), __( 'Allow the Sticky add to cart widget on product pages.', 'breefer' ) ),
						);

						foreach ( $breefer_woo_toggles as $breefer_key => $breefer_toggle ) :
							?>
							<div class="breefer-field">
								<div class="breefer-field-text">
									<label for="breefer-woo-<?php echo esc_attr( $breefer_key ); ?>"><?php echo esc_html( $breefer_toggle[0] ); ?></label>
									<p class="breefer-help"><?php echo esc_html( $breefer_toggle[1] ); ?></p>
								</div>
								<label class="breefer-toggle" for="breefer-woo-<?php echo esc_attr( $breefer_key ); ?>">
									<input type="checkbox" id="breefer-woo-<?php echo esc_attr( $breefer_key ); ?>" name="breefer_settings[woocommerce][<?php echo esc_attr( $breefer_key ); ?>]" value="1" <?php checked( ! empty( $breefer_woo[ $breefer_key ] ) ); ?>>
									<span class="breefer-toggle-track" aria-hidden="true"><span class="breefer-toggle-thumb"></span></span>
								</label>
							</div>
						<?php endforeach; ?>
					</section>

					<section class="breefer-card" id="editor">
						<header class="breefer-card-head">
							<div>
								<h2><?php esc_html_e( 'Editor', 'breefer' ); ?></h2>
								<p class="breefer-help"><?php esc_html_e( 'Defaults applied when a builder session opens.', 'breefer' ); ?></p>
							</div>
						</header>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-autosave"><?php esc_html_e( 'Autosave interval', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Seconds between background saves while editing.', 'breefer' ); ?></p>
							</div>
							<div class="breefer-field-control">
								<input
									class="breefer-input is-narrow"
									type="number"
									id="breefer-autosave"
									name="breefer_settings[editor][autosave_interval]"
									value="<?php echo esc_attr( (string) ( $breefer_editor['autosave_interval'] ?? 30 ) ); ?>"
									min="5"
									max="300"
									step="5"
								>
								<span class="breefer-unit"><?php esc_html_e( 'sec', 'breefer' ); ?></span>
							</div>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-history"><?php esc_html_e( 'Undo history', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Number of steps kept in memory and local storage.', 'breefer' ); ?></p>
							</div>
							<div class="breefer-field-control">
								<input
									class="breefer-input is-narrow"
									type="number"
									id="breefer-history"
									name="breefer_settings[editor][history_limit]"
									value="<?php echo esc_attr( (string) ( $breefer_editor['history_limit'] ?? 50 ) ); ?>"
									min="10"
									max="200"
									step="10"
								>
								<span class="breefer-unit"><?php esc_html_e( 'steps', 'breefer' ); ?></span>
							</div>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-canvas-width"><?php esc_html_e( 'Canvas width', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Desktop canvas width in pixels, before breakpoint scaling.', 'breefer' ); ?></p>
							</div>
							<div class="breefer-field-control">
								<input
									class="breefer-input is-narrow"
									type="number"
									id="breefer-canvas-width"
									name="breefer_settings[editor][canvas_width]"
									value="<?php echo esc_attr( (string) ( $breefer_editor['canvas_width'] ?? 1200 ) ); ?>"
									min="320"
									max="2560"
									step="10"
								>
								<span class="breefer-unit"><?php esc_html_e( 'px', 'breefer' ); ?></span>
							</div>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-default-editor"><?php esc_html_e( 'Use Breefer by default', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'New pages and posts open in Breefer instead of the WordPress editor. Existing content can still be edited in WordPress.', 'breefer' ); ?></p>
							</div>
							<label class="breefer-toggle" for="breefer-default-editor">
								<input type="checkbox" id="breefer-default-editor" name="breefer_settings[editor][default_editor]" value="1" <?php checked( ! empty( $breefer_editor['default_editor'] ) ); ?>>
								<span class="breefer-toggle-track" aria-hidden="true"><span class="breefer-toggle-thumb"></span></span>
							</label>
						</div>
					</section>

					<section class="breefer-card" id="performance">
						<header class="breefer-card-head">
							<div>
								<h2><?php esc_html_e( 'Performance', 'breefer' ); ?></h2>
								<p class="breefer-help"><?php esc_html_e( 'Frontend output stays static HTML by default. These switches control the optimisations around it.', 'breefer' ); ?></p>
							</div>
						</header>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-critical-css"><?php esc_html_e( 'Inline critical CSS', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Extract the rules a page uses and inline them in the head.', 'breefer' ); ?></p>
							</div>
							<label class="breefer-toggle" for="breefer-critical-css">
								<input type="checkbox" id="breefer-critical-css" name="breefer_settings[performance][critical_css]" value="1" <?php checked( ! empty( $breefer_performance['critical_css'] ) ); ?>>
								<span class="breefer-toggle-track" aria-hidden="true"><span class="breefer-toggle-thumb"></span></span>
							</label>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-lazy-islands"><?php esc_html_e( 'Lazy islands', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Defer interactive widgets and custom blocks until they are visible.', 'breefer' ); ?></p>
							</div>
							<label class="breefer-toggle" for="breefer-lazy-islands">
								<input type="checkbox" id="breefer-lazy-islands" name="breefer_settings[performance][lazy_islands]" value="1" <?php checked( ! empty( $breefer_performance['lazy_islands'] ) ); ?>>
								<span class="breefer-toggle-track" aria-hidden="true"><span class="breefer-toggle-thumb"></span></span>
							</label>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-render-cache"><?php esc_html_e( 'Cache rendered output', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Store the finished HTML per template and query, invalidated on save.', 'breefer' ); ?></p>
							</div>
							<label class="breefer-toggle" for="breefer-render-cache">
								<input type="checkbox" id="breefer-render-cache" name="breefer_settings[performance][render_cache]" value="1" <?php checked( ! empty( $breefer_performance['render_cache'] ) ); ?>>
								<span class="breefer-toggle-track" aria-hidden="true"><span class="breefer-toggle-thumb"></span></span>
							</label>
						</div>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-prefetch"><?php esc_html_e( 'Prefetch internal links', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Warm same-origin pages on hover with a small opt-in prefetcher.', 'breefer' ); ?></p>
							</div>
							<label class="breefer-toggle" for="breefer-prefetch">
								<input type="checkbox" id="breefer-prefetch" name="breefer_settings[performance][prefetch_links]" value="1" <?php checked( ! empty( $breefer_performance['prefetch_links'] ) ); ?>>
								<span class="breefer-toggle-track" aria-hidden="true"><span class="breefer-toggle-thumb"></span></span>
							</label>
						</div>

						<ul class="breefer-budget">
							<li><span><?php esc_html_e( 'Frontend JS', 'breefer' ); ?></span><strong>&lt; 15 KB</strong></li>
							<li><span><?php esc_html_e( 'Frontend CSS', 'breefer' ); ?></span><strong>&lt; 20 KB</strong></li>
							<li><span><?php esc_html_e( 'Lighthouse', 'breefer' ); ?></span><strong>95+</strong></li>
							<li><span><?php esc_html_e( 'TTFB delta', 'breefer' ); ?></span><strong>&lt; 5 ms</strong></li>
						</ul>
					</section>

					<section class="breefer-card" id="access">
						<header class="breefer-card-head">
							<div>
								<h2><?php esc_html_e( 'Access', 'breefer' ); ?></h2>
								<p class="breefer-help"><?php esc_html_e( 'Administrators always keep access. Choose the lowest role that should also be able to build.', 'breefer' ); ?></p>
							</div>
						</header>

						<div class="breefer-field">
							<div class="breefer-field-text">
								<label for="breefer-role"><?php esc_html_e( 'Builder role', 'breefer' ); ?></label>
								<p class="breefer-help"><?php esc_html_e( 'Grants the manage_breefer capability to the selected role.', 'breefer' ); ?></p>
							</div>
							<div class="breefer-field-control">
								<select class="breefer-input" id="breefer-role" name="breefer_settings[access][role]">
									<?php foreach ( $breefer_roles as $breefer_role_slug => $breefer_role_name ) : ?>
										<option value="<?php echo esc_attr( (string) $breefer_role_slug ); ?>" <?php selected( $breefer_active_role, (string) $breefer_role_slug ); ?>>
											<?php echo esc_html( (string) $breefer_role_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</section>

					<div class="breefer-savebar">
						<span class="breefer-help"><?php esc_html_e( 'Changes apply to the editor and to future renders.', 'breefer' ); ?></span>
						<?php submit_button( __( 'Save changes', 'breefer' ), 'primary', 'submit', false ); ?>
					</div>

				</div>
			</div>
		</form>
	</div>
</div>
