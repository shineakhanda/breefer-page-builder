<?php
/**
 * Frontend integration.
 *
 * @package Breefer
 */

namespace Breefer\Frontend;

use Breefer\Documents\Document;
use Breefer\Rendering\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the renderer into content, shortcodes, blocks and previews.
 */
final class Frontend {

	/**
	 * Page layout meta key.
	 */
	public const LAYOUT = '_breefer_layout';

	/**
	 * Renderer.
	 */
	private Renderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_frontend' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
		add_filter( 'template_include', array( $this, 'template_include' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_assets' ) );
		add_action( 'template_redirect', array( $this, 'maybe_preview' ) );
	}

	/**
	 * Use the canvas or full-width layout when selected.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function template_include( string $template ): string {
		if ( is_admin() || is_embed() || ! is_singular() ) {
			return $template;
		}

		// WooCommerce product/shop templates are handled by the compatibility layer.
		if ( function_exists( 'is_product' ) && ( is_product() || is_shop() ) ) {
			return $template;
		}

		$post = get_post();

		if ( null === $post || ! Document::is_enabled( $post->ID ) ) {
			return $template;
		}

		$layout = (string) get_post_meta( $post->ID, self::LAYOUT, true );

		$file = '';

		if ( 'canvas' === $layout ) {
			$file = 'templates/canvas.php';
		} elseif ( 'full' === $layout ) {
			$file = 'templates/full-width.php';
		}

		if ( '' === $file ) {
			return $template;
		}

		$path = BREEFER_DIR . $file;

		return is_readable( $path ) ? $path : $template;
	}

	/**
	 * Add layout body classes.
	 *
	 * @param array<int, string> $classes Classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( ! is_singular() ) {
			return $classes;
		}

		$layout = (string) get_post_meta( (int) get_queried_object_id(), self::LAYOUT, true );

		if ( in_array( $layout, array( 'full', 'canvas' ), true ) ) {
			$classes[] = 'breefer-layout-' . $layout;
		}

		return $classes;
	}

	/**
	 * Render the queried document.
	 *
	 * @return string
	 */
	public function render_current(): string {
		$id = (int) get_queried_object_id();

		return $id > 0 ? $this->renderer->render_post( $id ) : '';
	}

	/**
	 * Register shortcode and block.
	 *
	 * @return void
	 */
	public function register_frontend(): void {
		add_shortcode( 'breefer', array( $this, 'shortcode' ) );

		register_block_type(
			'breefer/template',
			array(
				'api_version'     => 2,
				'attributes'      => array(
					'id' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Enqueue frontend CSS when the page needs it.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		if ( null === $post ) {
			return;
		}

		$enabled      = Document::is_enabled( $post->ID );
		$has_block    = has_block( 'breefer/template', $post );
		$has_shortcode = has_shortcode( (string) $post->post_content, 'breefer' );
		$needed       = $enabled || $has_block || $has_shortcode;

		if ( ! $needed ) {
			return;
		}

		FrontendAssets::enqueue();
	}

	/**
	 * Replace post content when Breefer is enabled.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function filter_content( string $content ): string {
		if ( is_admin() || ! is_singular() ) {
			return $content;
		}

		$post = get_post();

		if ( null === $post || ! Document::is_enabled( $post->ID ) ) {
			return $content;
		}

		// Only replace the main queried post, never related entries in a loop.
		if ( (int) get_queried_object_id() !== $post->ID ) {
			return $content;
		}

		$output = $this->renderer->render_post( $post->ID );

		// An enabled page with an empty canvas keeps its original content.
		return '' !== $output ? $output : $content;
	}

	/**
	 * Shortcode output.
	 *
	 * @param mixed $atts Attributes.
	 * @return string
	 */
	public function shortcode( mixed $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			is_array( $atts ) ? $atts : array(),
			'breefer'
		);

		$id = absint( $atts['id'] );

		if ( $id <= 0 ) {
			$id = (int) get_the_ID();
		}

		return $id > 0 ? $this->renderer->render_post( $id ) : '';
	}

	/**
	 * Render the Breefer block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( array $attributes ): string {
		$id = isset( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;

		return $id > 0 ? $this->renderer->render_post( $id ) : '';
	}

	/**
	 * Register the block editor script.
	 *
	 * @return void
	 */
	public function block_editor_assets(): void {
		wp_enqueue_script(
			'breefer-block',
			BREEFER_URL . 'assets/js/block.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-components',
				'wp-server-side-render',
				'wp-block-editor',
				'wp-plugins',
				'wp-edit-post',
				'wp-data',
				'wp-i18n',
			),
			BREEFER_VERSION,
			true
		);

		$templates = get_posts(
			array(
				'post_type'        => 'breefer_template',
				'post_status'      => array( 'publish', 'draft' ),
				'numberposts'      => 100,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);

		$options = array(
			array(
				'label' => __( 'Select a template', 'breefer' ),
				'value' => 0,
			),
		);

		foreach ( $templates as $template ) {
			$options[] = array(
				'label' => get_the_title( $template ),
				'value' => $template->ID,
			);
		}

		wp_add_inline_script(
			'breefer-block',
			'window.breeferBlock = ' . wp_json_encode(
				array(
					'templates'     => $options,
					'editorUrl'     => admin_url( 'admin.php?page=breefer-editor&new=1' ),
					'editorUrlBase' => admin_url( 'admin.php?page=breefer-editor&id=' ),
					'label'         => __( 'Breefer Canvas', 'breefer' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Public preview URL for a document.
	 *
	 * @param int $id Post id.
	 * @return string
	 */
	public static function preview_url( int $id ): string {
		return add_query_arg( 'breefer_preview', $id, home_url( '/' ) );
	}

	/**
	 * Render a standalone preview for logged-in editors.
	 *
	 * @return void
	 */
	public function maybe_preview(): void {
		if ( ! isset( $_GET['breefer_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$id = absint( $_GET['breefer_preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $id <= 0 || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}

		$post = get_post( $id );

		if ( null === $post ) {
			return;
		}

		$html = $this->renderer->render_post( $id );
		$css  = add_query_arg( 'ver', BREEFER_VERSION, BREEFER_URL . 'assets/css/frontend.css' );
		$wcss = add_query_arg( 'ver', BREEFER_VERSION, BREEFER_URL . 'assets/css/widgets.css' );
		$js   = add_query_arg( 'ver', BREEFER_VERSION, BREEFER_URL . 'assets/js/frontend.js' );

		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		echo '<!doctype html><html ' . get_language_attributes() . '><head>';
		echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		echo '<title>' . esc_html( get_the_title( $id ) ) . '</title>';
		echo '<link rel="stylesheet" href="' . esc_url( $css ) . '">';
		echo '<link rel="stylesheet" href="' . esc_url( $wcss ) . '">';
		echo '<style>' . \Breefer\Utils\DesignSystem::css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated from validated tokens.
		echo '</head><body class="breefer-preview-body">';
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes its own output.
		echo '<script src="' . esc_url( $js ) . '"></script>';
		echo '</body></html>';
		exit;
	}
}
