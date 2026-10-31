<?php
/**
 * Full-screen builder screen.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\Compatibility\WooCommerce;
use Breefer\Theme\TemplateTypes;
use Breefer\Utils\DesignSystem;
use Breefer\Documents\Document;
use Breefer\Frontend\Frontend;
use Breefer\Utils\Capabilities;
use Breefer\Utils\Icons;
use Breefer\Utils\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the React editor mount point and its boot payload.
 */
final class EditorPage {

	/**
	 * Page slug.
	 */
	public const SLUG = 'breefer-editor';

	/**
	 * Cached manifest.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $manifest_cache = null;

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_create' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Register a hidden admin page.
	 *
	 * @return void
	 */
	public function menu(): void {
		add_submenu_page(
			null,
			__( 'Breefer Editor', 'breefer' ),
			__( 'Breefer Editor', 'breefer' ),
			'edit_posts',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Whether the current request is the editor.
	 *
	 * @return bool
	 */
	private function is_editor_screen(): bool {
		if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		return self::SLUG === sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Add the full-screen body class.
	 *
	 * @param string $classes Classes.
	 * @return string
	 */
	public function body_class( string $classes ): string {
		return $this->is_editor_screen() ? $classes . ' breefer-editor-screen' : $classes;
	}

	/**
	 * Create a document or bounce before any output is sent.
	 *
	 * @return void
	 */
	public function maybe_create(): void {
		if ( ! $this->is_editor_screen() ) {
			return;
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $id > 0 ) {
			return;
		}

		$is_new = isset( $_GET['new'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& '1' === sanitize_text_field( wp_unslash( (string) $_GET['new'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $is_new && current_user_can( 'edit_posts' ) ) {
			$new_type = isset( $_GET['type'] ) && is_string( $_GET['type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				? sanitize_key( wp_unslash( $_GET['type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				: 'template';

			$allowed = array( 'page', 'post', 'template' );

			if ( ! in_array( $new_type, $allowed, true ) ) {
				$new_type = 'template';
			}

			if ( 'page' === $new_type && ! current_user_can( 'edit_pages' ) ) {
				$new_type = 'template';
			}

			$title = 'template' === $new_type
				? __( 'Untitled template', 'breefer' )
				: ( 'page' === $new_type ? __( 'Untitled page', 'breefer' ) : __( 'Untitled post', 'breefer' ) );

			$template_type = isset( $_GET['template_type'] ) && is_string( $_GET['template_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				? sanitize_key( wp_unslash( $_GET['template_type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				: '';

			$created = BuilderEntry::create_document(
				'template' === $new_type ? 'breefer_template' : $new_type,
				$title,
				true,
				$template_type
			);

			if ( $created > 0 ) {
				wp_safe_redirect( admin_url( 'admin.php?page=breefer-editor&id=' . $created ) );
				exit;
			}
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=breefer_template' ) );
		exit;
	}

	/**
	 * Enqueue editor assets from the Vite manifest.
	 *
	 * @return void
	 */
	public function assets(): void {
		if ( ! $this->is_editor_screen() ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'breefer-editor-chrome',
			BREEFER_URL . 'assets/css/editor-admin.css',
			array( 'dashicons' ),
			$this->asset_version( 'assets/css/editor-admin.css' )
		);

		wp_enqueue_style(
			'breefer-editor-widgets',
			BREEFER_URL . 'assets/css/widgets.css',
			array( 'breefer-editor-chrome' ),
			$this->asset_version( 'assets/css/widgets.css' )
		);

		wp_add_inline_style( 'breefer-editor-widgets', DesignSystem::css() );

		$manifest = $this->manifest();
		$entry = isset( $manifest['src/editor.tsx'] ) && is_array( $manifest['src/editor.tsx'] )
			? $manifest['src/editor.tsx']
			: null;

		if ( null === $entry || empty( $entry['file'] ) ) {
			return;
		}

		$styles = array();

		if ( isset( $entry['css'] ) && is_array( $entry['css'] ) ) {
			$styles = $entry['css'];
		} else {
			// With cssCodeSplit disabled Vite lists the stylesheet as its own
			// manifest entry instead of attaching it to the JS entry.
			foreach ( $manifest as $item ) {
				if ( is_array( $item ) && isset( $item['file'] ) && is_string( $item['file'] ) && str_ends_with( $item['file'], '.css' ) ) {
					$styles[] = $item['file'];
				}
			}
		}

		wp_enqueue_script(
			'breefer-editor',
			BREEFER_URL . 'build/' . ltrim( (string) $entry['file'], '/' ),
			array(),
			BREEFER_VERSION,
			true
		);

		foreach ( array_unique( $styles ) as $index => $file ) {
			wp_enqueue_style(
				'breefer-editor-' . (int) $index,
				BREEFER_URL . 'build/' . ltrim( (string) $file, '/' ),
				array(),
				BREEFER_VERSION
			);
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $id > 0 ) {
			wp_localize_script( 'breefer-editor', 'breeferEditor', $this->boot_data( $id ) );
		}
	}

	/**
	 * Render the editor screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capabilities::can_manage() && ! current_user_can( 'edit_posts' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to use the builder.', 'breefer' ),
				esc_html__( 'Forbidden', 'breefer' ),
				array( 'response' => 403 )
			);
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $id <= 0 ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die(
				esc_html__( 'You do not have permission to edit this document.', 'breefer' ),
				esc_html__( 'Forbidden', 'breefer' ),
				array( 'response' => 403 )
			);
		}

		$manifest = $this->manifest();
		$entry    = isset( $manifest['src/editor.tsx'] ) ? $manifest['src/editor.tsx'] : null;

		if ( ! is_array( $entry ) || empty( $entry['file'] ) ) {
			printf(
				'<div class="notice notice-error breefer-notice"><p>%s</p></div>',
				esc_html__( 'The editor bundle is missing. Run "npm install" and "npm run build" in the plugin directory.', 'breefer' )
			);

			return;
		}

		?>
		<div id="breefer-editor-root">
			<div class="breefer-boot">
				<span class="breefer-boot-mark" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Loading Breefer…', 'breefer' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Boot payload for the React app.
	 *
	 * @param int $id Post id.
	 * @return array<string, mixed>
	 */
	private function boot_data( int $id ): array {
		return array(
			'postId'       => $id,
			'title'        => get_the_title( $id ),
			'status'       => get_post_status( $id ),
			'postType'     => get_post_type( $id ),
			'restUrl'      => esc_url_raw( rest_url( 'breefer/v1/' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'data'         => Document::get( $id ),
			'previewUrl'   => Frontend::preview_url( $id ),
			'templatesUrl' => admin_url( 'edit.php?post_type=breefer_template' ),
			'dashboardUrl' => admin_url( 'admin.php?page=breefer' ),
			'autosave'     => max( 5, (int) Options::get( 'editor.autosave_interval', 30 ) ),
			'historyLimit' => max( 10, (int) Options::get( 'editor.history_limit', 50 ) ),
			'canvasWidth'  => max( 320, (int) Options::get( 'editor.canvas_width', 1200 ) ),
			'icons'        => Icons::all(),
			'wooType'      => (string) get_post_meta( $id, TemplateTypes::META, true ),
			'wooAvailable' => WooCommerce::is_active(),
			'templateTypes' => array_map(
				static fn( string $slug, array $type ): array => array(
					'slug'        => $slug,
					'label'       => $type['label'],
					'group'       => $type['group'],
					'conditional' => $type['conditional'],
					'hint'        => $type['hint'],
				),
				array_keys( TemplateTypes::available() ),
				array_values( TemplateTypes::available() )
			),
			'conditions'   => TemplateTypes::conditions(),
			'condition'    => (string) get_post_meta( $id, TemplateTypes::CONDITION, true ) ?: 'all',
			'designTokens' => DesignSystem::tokens_for_editor(),
			'menus'        => $this->menus(),
			'postTypes'    => $this->post_types(),
			'layout'       => (string) get_post_meta( $id, Frontend::LAYOUT, true ) ?: 'theme',
		);
	}

	/**
	 * Navigation menus for the Menu widget.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	private function menus(): array {
		$menus = array();

		foreach ( wp_get_nav_menus() as $menu ) {
			$menus[] = array(
				'value' => (string) $menu->term_id,
				'label' => $menu->name,
			);
		}

		return $menus;
	}

	/**
	 * Public post types for the Posts grid widget.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	private function post_types(): array {
		$types = array();

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $slug => $object ) {
			if ( in_array( $slug, array( 'attachment', 'breefer_template' ), true ) ) {
				continue;
			}

			$types[] = array(
				'value' => (string) $slug,
				'label' => $object->labels->name,
			);
		}

		return $types;
	}

	/**
	 * Read the Vite manifest once.
	 *
	 * @return array<string, mixed>
	 */
	private function manifest(): array {
		if ( null !== $this->manifest_cache ) {
			return $this->manifest_cache;
		}

		$path = BREEFER_DIR . 'build/manifest.json';

		if ( ! is_readable( $path ) ) {
			$this->manifest_cache = array();

			return $this->manifest_cache;
		}

		$raw     = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;

		$this->manifest_cache = is_array( $decoded ) ? $decoded : array();

		return $this->manifest_cache;
	}

	/**
	 * Cache-busting version.
	 *
	 * @param string $relative Relative path.
	 * @return string
	 */
	private function asset_version( string $relative ): string {
		$path = BREEFER_DIR . $relative;

		return is_readable( $path ) ? (string) filemtime( $path ) : BREEFER_VERSION;
	}
}
