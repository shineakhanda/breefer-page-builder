<?php
/**
 * Dashboard screen.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\PostTypes\Templates;
use Breefer\Utils\Capabilities;
use Breefer\Utils\Options;
use Breefer\Utils\Palette;
use Breefer\Utils\View;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Breefer dashboard.
 */
final class DashboardPage {

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'breefer' ),
				esc_html__( 'Forbidden', 'breefer' ),
				array( 'response' => 403 )
			);
		}

		$counts = (array) wp_count_posts( Templates::TYPE );
		$total  = 0;

		foreach ( array( 'publish', 'draft', 'private', 'pending' ) as $status ) {
			$total += (int) ( $counts[ $status ] ?? 0 );
		}

		View::render(
			'admin/dashboard',
			array(
				'version'          => BREEFER_VERSION,
				'palette'          => (string) Options::get( 'appearance.palette', 'violet' ),
				'palettes'         => Palette::all(),
				'environment'      => $this->environment(),
				'settings_url'     => admin_url( 'admin.php?page=breefer-settings' ),
				'templates_url'    => admin_url( 'edit.php?post_type=' . Templates::TYPE ),
				'new_template_url' => admin_url( 'admin.php?page=breefer-editor&new=1' ),
				'new_page_url'     => admin_url( 'admin.php?page=breefer-new-page' ),
				'pages_url'        => admin_url( 'edit.php?post_type=page' ),
				'recent_templates' => $this->recent_templates(),
				'templates_count'  => $total,
			)
		);
	}

	/**
	 * Recent templates.
	 *
	 * @return array<int, array{title: string, edit_url: string, status: string}>
	 */
	private function recent_templates(): array {
		$posts = get_posts(
			array(
				'post_type'        => Templates::TYPE,
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'numberposts'      => 4,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => true,
			)
		);

		$templates = array();

		foreach ( $posts as $post ) {
			$templates[] = array(
				'title'    => get_the_title( $post ),
				'edit_url' => admin_url( 'admin.php?page=breefer-editor&id=' . $post->ID ),
				'status'   => $post->post_status,
			);
		}

		return $templates;
	}

	/**
	 * Collect environment checks.
	 *
	 * @return array<int, array{label: string, value: string, state: string, hint: string}>
	 */
	private function environment(): array {
		$php_version = PHP_VERSION;
		$wp_version  = (string) get_bloginfo( 'version' );
		$memory      = $this->memory_limit();
		$uploads     = wp_upload_dir();
		$uploads_ok  = empty( $uploads['error'] ) && wp_is_writable( (string) $uploads['basedir'] );

		return array(
			array(
				'label' => __( 'PHP runtime', 'breefer' ),
				'value' => $php_version,
				'state' => version_compare( $php_version, BREEFER_MIN_PHP, '>=' ) ? 'ok' : 'warn',
				'hint'  => __( 'Breefer requires PHP 8.2 or newer.', 'breefer' ),
			),
			array(
				'label' => __( 'WordPress', 'breefer' ),
				'value' => $wp_version,
				'state' => version_compare( $wp_version, BREEFER_MIN_WP, '>=' ) ? 'ok' : 'warn',
				'hint'  => __( 'Tested against the current 6.x release line.', 'breefer' ),
			),
			array(
				'label' => __( 'Object cache', 'breefer' ),
				'value' => wp_using_ext_object_cache() ? __( 'Persistent', 'breefer' ) : __( 'Database', 'breefer' ),
				'state' => wp_using_ext_object_cache() ? 'ok' : 'info',
				'hint'  => __( 'Redis or Memcached makes cached renders substantially cheaper.', 'breefer' ),
			),
			array(
				'label' => __( 'Memory limit', 'breefer' ),
				'value' => $memory['value'],
				'state' => $memory['state'],
				'hint'  => __( '256 MB or more is recommended for the editor build.', 'breefer' ),
			),
			array(
				'label' => __( 'Uploads directory', 'breefer' ),
				'value' => $uploads_ok ? __( 'Writable', 'breefer' ) : __( 'Not writable', 'breefer' ),
				'state' => $uploads_ok ? 'ok' : 'warn',
				'hint'  => __( 'Only /wp-content/uploads/breefer is used for generated assets.', 'breefer' ),
			),
			array(
				'label' => __( 'Editor bundle', 'breefer' ),
				'value' => is_readable( BREEFER_DIR . 'build/manifest.json' ) ? __( 'Built', 'breefer' ) : __( 'Missing', 'breefer' ),
				'state' => is_readable( BREEFER_DIR . 'build/manifest.json' ) ? 'ok' : 'warn',
				'hint'  => __( 'The React editor is served from build/ after npm run build.', 'breefer' ),
			),
			array(
				'label' => __( 'WooCommerce', 'breefer' ),
				'value' => defined( 'WC_VERSION' ) ? (string) constant( 'WC_VERSION' ) : __( 'Not active', 'breefer' ),
				'state' => defined( 'WC_VERSION' ) ? 'ok' : 'info',
				'hint'  => __( 'Woo widgets and template hijacks stay dormant until WooCommerce is active.', 'breefer' ),
			),
			array(
				'label' => __( 'Page cache plugin', 'breefer' ),
				'value' => $this->page_cache_label(),
				'state' => 'info',
				'hint'  => __( 'Breefer purges through each plugin’s native hooks when a template is saved.', 'breefer' ),
			),
		);
	}

	/**
	 * Human readable memory limit.
	 *
	 * @return array{value: string, state: string}
	 */
	private function memory_limit(): array {
		$limit = ini_get( 'memory_limit' );

		if ( ! is_string( $limit ) || '' === $limit ) {
			return array(
				'value' => __( 'Unknown', 'breefer' ),
				'state' => 'info',
			);
		}

		$bytes = wp_convert_hr_to_bytes( $limit );

		if ( -1 === $bytes ) {
			return array(
				'value' => __( 'Unlimited', 'breefer' ),
				'state' => 'ok',
			);
		}

		return array(
			'value' => (string) size_format( $bytes ),
			'state' => $bytes >= 256 * MB_IN_BYTES ? 'ok' : 'warn',
		);
	}

	/**
	 * Detect a known page cache plugin.
	 *
	 * @return string
	 */
	private function page_cache_label(): string {
		$known = array(
			'WP_ROCKET_VERSION' => 'WP Rocket',
			'LSCWP_V'           => 'LiteSpeed Cache',
			'W3TC'              => 'W3 Total Cache',
			'WPSC_VERSION'      => 'WP Super Cache',
			'CE_FILE'           => 'Cache Enabler',
		);

		foreach ( $known as $constant => $label ) {
			if ( defined( $constant ) ) {
				return $label;
			}
		}

		return __( 'None detected', 'breefer' );
	}
}
