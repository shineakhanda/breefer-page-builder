<?php
/**
 * Dashboard template.
 *
 * @package Breefer
 *
 * @var array<string, mixed> $args Template data.
 */

defined( 'ABSPATH' ) || exit;

$breefer_environment = isset( $args['environment'] ) && is_array( $args['environment'] ) ? $args['environment'] : array();
$breefer_palettes    = isset( $args['palettes'] ) && is_array( $args['palettes'] ) ? $args['palettes'] : array();
$breefer_templates   = isset( $args['recent_templates'] ) && is_array( $args['recent_templates'] ) ? $args['recent_templates'] : array();
$breefer_active      = isset( $args['palette'] ) ? (string) $args['palette'] : 'violet';
$breefer_new_url     = isset( $args['new_template_url'] ) ? (string) $args['new_template_url'] : '#';
$breefer_new_page_url = isset( $args['new_page_url'] ) ? (string) $args['new_page_url'] : '#';
$breefer_templates_url = isset( $args['templates_url'] ) ? (string) $args['templates_url'] : '#';
$breefer_settings_url  = isset( $args['settings_url'] ) ? (string) $args['settings_url'] : '#';
$breefer_pages_url     = isset( $args['pages_url'] ) ? (string) $args['pages_url'] : '#';
$breefer_ready         = 0;

foreach ( $breefer_environment as $breefer_check ) {
	if ( isset( $breefer_check['state'] ) && 'ok' === $breefer_check['state'] ) {
		++$breefer_ready;
	}
}
?>
<div class="breefer-admin">
	<div class="breefer-wrap">

		<section class="breefer-hero">
			<div class="breefer-hero-copy">
				<span class="breefer-eyebrow">
					<?php
					printf(
						/* translators: %s: plugin version. */
						esc_html__( 'Breefer %s · Builder core', 'breefer' ),
						esc_html( isset( $args['version'] ) ? (string) $args['version'] : '' )
					);
					?>
				</span>
				<h1><?php esc_html_e( 'Design it here. Ship static HTML.', 'breefer' ); ?></h1>
				<p>
					<?php
					esc_html_e(
						'The builder is the default editor for new pages and posts. A theme builder for headers, footers, posts, archives and WooCommerce pages, a global design system, and 50+ widgets.',
						'breefer'
					);
					?>
				</p>
				<div class="breefer-hero-actions">
					<a class="breefer-btn is-primary" href="<?php echo esc_url( $breefer_new_url ); ?>">
						<?php esc_html_e( 'Create a template', 'breefer' ); ?>
					</a>
					<a class="breefer-btn is-ghost" href="<?php echo esc_url( $breefer_templates_url ); ?>">
						<?php esc_html_e( 'Browse templates', 'breefer' ); ?>
					</a>
				</div>
			</div>

			<div class="breefer-hero-stats">
				<div class="breefer-hero-stat">
					<span><?php esc_html_e( 'Widgets', 'breefer' ); ?></span>
					<strong><?php $breefer_widgets = \Breefer\Plugin::instance()->get( 'widgets' ); echo esc_html( (string) ( $breefer_widgets instanceof \Breefer\Rendering\WidgetRegistry ? count( $breefer_widgets->types() ) : 0 ) ); ?></strong>
				</div>
				<div class="breefer-hero-stat">
					<span><?php esc_html_e( 'Frontend JS', 'breefer' ); ?></span>
					<strong>&lt; 2 KB</strong>
				</div>
				<div class="breefer-hero-stat">
					<span><?php esc_html_e( 'Templates', 'breefer' ); ?></span>
					<strong><?php echo esc_html( (string) ( $args['templates_count'] ?? count( $breefer_templates ) ) ); ?></strong>
				</div>
			</div>
		</section>

		<div class="breefer-grid">

			<section class="breefer-card breefer-span-2">
				<header class="breefer-card-head">
					<div>
						<h2><?php esc_html_e( 'Start building', 'breefer' ); ?></h2>
						<p class="breefer-help"><?php esc_html_e( 'Three ways into the builder.', 'breefer' ); ?></p>
					</div>
				</header>

				<div class="breefer-steps">
					<a class="breefer-step-card" href="<?php echo esc_url( $breefer_new_url ); ?>">
						<strong><?php esc_html_e( 'New template', 'breefer' ); ?></strong>
						<span><?php esc_html_e( 'Start from a seeded canvas and save a reusable design.', 'breefer' ); ?></span>
					</a>
					<a class="breefer-step-card" href="<?php echo esc_url( $breefer_new_page_url ); ?>">
						<strong><?php esc_html_e( 'New page', 'breefer' ); ?></strong>
						<span><?php esc_html_e( 'Start a page in Breefer instead of the block editor.', 'breefer' ); ?></span>
					</a>
					<a class="breefer-step-card" href="<?php echo esc_url( $breefer_settings_url ); ?>">
						<strong><?php esc_html_e( 'Editor defaults', 'breefer' ); ?></strong>
						<span><?php esc_html_e( 'Tune autosave, history and the canvas width.', 'breefer' ); ?></span>
					</a>
				</div>
			</section>

			<section class="breefer-card">
				<header class="breefer-card-head">
					<div>
						<h2><?php esc_html_e( 'Design language', 'breefer' ); ?></h2>
						<p class="breefer-help"><?php esc_html_e( 'Shared with the Minimal Admin palette family.', 'breefer' ); ?></p>
					</div>
				</header>

				<div class="breefer-palettes is-compact">
					<?php foreach ( $breefer_palettes as $breefer_slug => $breefer_palette ) : ?>
						<?php
						$breefer_is_active = (string) $breefer_slug === $breefer_active;
						$breefer_sidebar   = isset( $breefer_palette['sidebar'] ) ? (string) $breefer_palette['sidebar'] : '#4b2d8a';
						$breefer_sidebar2  = isset( $breefer_palette['sidebar-2'] ) ? (string) $breefer_palette['sidebar-2'] : '#352066';
						$breefer_accent    = isset( $breefer_palette['accent'] ) ? (string) $breefer_palette['accent'] : '#7c5cfc';
						?>
						<div class="breefer-palette is-static<?php echo $breefer_is_active ? ' is-selected' : ''; ?>">
							<span class="breefer-palette-swatch" style="background:linear-gradient(160deg, <?php echo esc_attr( $breefer_sidebar ); ?>, <?php echo esc_attr( $breefer_sidebar2 ); ?>);">
								<i style="background:<?php echo esc_attr( $breefer_accent ); ?>;"></i>
							</span>
							<span class="breefer-palette-meta">
								<strong><?php echo esc_html( isset( $breefer_palette['label'] ) ? (string) $breefer_palette['label'] : (string) $breefer_slug ); ?></strong>
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
							<?php if ( $breefer_is_active ) : ?>
								<span class="breefer-palette-check" aria-hidden="true"></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<a class="breefer-link" href="<?php echo esc_url( $breefer_settings_url ); ?>#appearance">
					<?php esc_html_e( 'Change appearance', 'breefer' ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			</section>

			<section class="breefer-card breefer-span-2">
				<header class="breefer-card-head">
					<div>
						<h2><?php esc_html_e( 'Environment', 'breefer' ); ?></h2>
						<p class="breefer-help"><?php esc_html_e( 'What the current server gives Breefer to work with.', 'breefer' ); ?></p>
					</div>
					<span class="breefer-pill is-ok">
						<?php
						printf(
							/* translators: 1: passing checks, 2: total checks. */
							esc_html__( '%1$d of %2$d healthy', 'breefer' ),
							(int) $breefer_ready,
							count( $breefer_environment )
						);
						?>
					</span>
				</header>

				<ul class="breefer-status-list">
					<?php foreach ( $breefer_environment as $breefer_check ) : ?>
						<?php
						$breefer_state = isset( $breefer_check['state'] ) ? (string) $breefer_check['state'] : 'info';
						$breefer_class = in_array( $breefer_state, array( 'ok', 'warn', 'info' ), true ) ? $breefer_state : 'info';
						?>
						<li class="breefer-status-row">
							<span class="breefer-dot is-<?php echo esc_attr( $breefer_class ); ?>" aria-hidden="true"></span>
							<span class="breefer-status-label">
								<strong><?php echo esc_html( isset( $breefer_check['label'] ) ? (string) $breefer_check['label'] : '' ); ?></strong>
								<small><?php echo esc_html( isset( $breefer_check['hint'] ) ? (string) $breefer_check['hint'] : '' ); ?></small>
							</span>
							<code class="breefer-status-value"><?php echo esc_html( isset( $breefer_check['value'] ) ? (string) $breefer_check['value'] : '' ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="breefer-card">
				<header class="breefer-card-head">
					<div>
						<h2><?php esc_html_e( 'Recent templates', 'breefer' ); ?></h2>
						<p class="breefer-help"><?php esc_html_e( 'Pick up where you left off.', 'breefer' ); ?></p>
					</div>
				</header>

				<?php if ( array() === $breefer_templates ) : ?>
					<p class="breefer-help"><?php esc_html_e( 'No templates yet. Create one to see it here.', 'breefer' ); ?></p>
				<?php else : ?>
					<ul class="breefer-template-list">
						<?php foreach ( $breefer_templates as $breefer_template ) : ?>
							<li>
								<a href="<?php echo esc_url( isset( $breefer_template['edit_url'] ) ? (string) $breefer_template['edit_url'] : '#' ); ?>">
									<?php echo esc_html( isset( $breefer_template['title'] ) ? (string) $breefer_template['title'] : '' ); ?>
								</a>
								<small><?php echo esc_html( isset( $breefer_template['status'] ) ? (string) $breefer_template['status'] : '' ); ?></small>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<a class="breefer-link" href="<?php echo esc_url( $breefer_templates_url ); ?>">
					<?php esc_html_e( 'All templates', 'breefer' ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			</section>

			<section class="breefer-card breefer-span-2">
				<header class="breefer-card-head">
					<div>
						<h2><?php esc_html_e( 'Render pipeline', 'breefer' ); ?></h2>
						<p class="breefer-help"><?php esc_html_e( 'Every page follows the same four stages. Nothing heavier ships than the page actually uses.', 'breefer' ); ?></p>
					</div>
				</header>

				<ol class="breefer-pipeline">
					<li>
						<span class="breefer-step">01</span>
						<div>
							<strong><?php esc_html_e( 'Author', 'breefer' ); ?></strong>
							<p><?php esc_html_e( 'The React editor writes a portable JSON tree. No shortcodes, no serialized markup.', 'breefer' ); ?></p>
						</div>
					</li>
					<li>
						<span class="breefer-step">02</span>
						<div>
							<strong><?php esc_html_e( 'Render', 'breefer' ); ?></strong>
							<p><?php esc_html_e( 'PHP renderers emit semantic HTML and record the CSS each widget really used.', 'breefer' ); ?></p>
						</div>
					</li>
					<li>
						<span class="breefer-step">03</span>
						<div>
							<strong><?php esc_html_e( 'Extract', 'breefer' ); ?></strong>
							<p><?php esc_html_e( 'Node CSS is scoped and inlined with the content; unused rules never ship.', 'breefer' ); ?></p>
						</div>
					</li>
					<li>
						<span class="breefer-step">04</span>
						<div>
							<strong><?php esc_html_e( 'Hydrate', 'breefer' ); ?></strong>
							<p><?php esc_html_e( 'Interactive islands arrive later. A basic Breefer page ships zero JavaScript.', 'breefer' ); ?></p>
						</div>
					</li>
				</ol>
			</section>

			<section class="breefer-card">
				<header class="breefer-card-head">
					<div>
						<h2><?php esc_html_e( 'Developer API', 'breefer' ); ?></h2>
						<p class="breefer-help"><?php esc_html_e( 'Filters are stable from the first release.', 'breefer' ); ?></p>
					</div>
					<button type="button" class="breefer-btn is-small" data-breefer-copy="#breefer-hook-example" data-breefer-label="<?php esc_attr_e( 'Copy snippet', 'breefer' ); ?>">
						<?php esc_html_e( 'Copy snippet', 'breefer' ); ?>
					</button>
				</header>

				<pre class="breefer-code" id="breefer-hook-example"><code><?php echo esc_html( "add_filter( 'breefer/render/widget', function ( \$html, \$widget, \$settings ) {\n\treturn \$html;\n}, 10, 3 );" ); ?></code></pre>
				<p class="breefer-help"><?php esc_html_e( 'Widgets, controls and editor panels can also be registered from PHP and JavaScript.', 'breefer' ); ?></p>
			</section>

		</div>
	</div>
</div>
