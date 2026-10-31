<?php
/**
 * Theme builder runtime.
 *
 * @package Breefer
 */

namespace Breefer\Theme;

use Breefer\Documents\Document;
use Breefer\Frontend\Frontend;
use Breefer\Frontend\FrontendAssets;
use Breefer\PostTypes\Templates;
use Breefer\Rendering\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Swaps theme headers, footers and body templates for Breefer templates.
 *
 * Body templates (single, archive, 404, shop, cart ...) take over the page with
 * template_include. Headers and footers hook get_header/get_footer on classic
 * themes and the template-part block on block themes.
 */
final class ThemeBuilder {

	/**
	 * Renderer.
	 */
	private Renderer $renderer;

	/**
	 * Resolved template ids per type for this request.
	 *
	 * @var array<string, int>
	 */
	private array $resolved = array();

	/**
	 * Regions already emitted on this request, to avoid double output.
	 *
	 * @var array<string, bool>
	 */
	private array $emitted = array();

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
		add_filter( 'template_include', array( $this, 'template_include' ), 30 );
		add_action( 'get_header', array( $this, 'classic_header' ), 5, 1 );
		add_action( 'get_footer', array( $this, 'classic_footer' ), 5, 1 );
		add_filter( 'pre_render_block', array( $this, 'block_part' ), 10, 2 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 5 );
	}

	/**
	 * Find the template assigned to a type.
	 *
	 * @param string $type Template type.
	 * @return int Post id, or 0.
	 */
	public function find( string $type ): int {
		if ( isset( $this->resolved[ $type ] ) ) {
			return $this->resolved[ $type ];
		}

		$ids = get_posts(
			array(
				'post_type'        => Templates::TYPE,
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'numberposts'      => 25,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'meta_key'         => TemplateTypes::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);

		$best       = 0;
		$best_score = -1;
		$types      = TemplateTypes::all();

		foreach ( $ids as $id ) {
			$id    = (int) $id;
			$score = 0;

			if ( ! empty( $types[ $type ]['conditional'] ) ) {
				$condition = (string) get_post_meta( $id, TemplateTypes::CONDITION, true );
				$condition = '' === $condition ? 'all' : $condition;

				if ( ! TemplateTypes::matches( $condition ) ) {
					continue;
				}

				// A specific condition beats "entire site"; ties go to the newest.
				$score = 'all' === $condition ? 1 : 2;
			} else {
				$score = 1;
			}

			if ( $score > $best_score ) {
				$best       = $id;
				$best_score = $score;
			}
		}

		$this->resolved[ $type ] = $best;

		return $best;
	}

	/**
	 * Page layout chosen for the queried page ("theme", "full" or "canvas").
	 *
	 * @return string
	 */
	private function page_layout(): string {
		if ( ! is_singular() ) {
			return 'theme';
		}

		$layout = (string) get_post_meta( (int) get_queried_object_id(), Frontend::LAYOUT, true );

		return in_array( $layout, array( 'full', 'canvas' ), true ) ? $layout : 'theme';
	}

	/**
	 * Whether the queried post is designed on its own in Breefer.
	 *
	 * Such pages keep their own layout instead of being wrapped by a
	 * single post / single page template.
	 *
	 * @return bool
	 */
	private function is_self_designed(): bool {
		return is_singular() && Document::is_enabled( (int) get_queried_object_id() );
	}
	/**
	 * Take over the page for body templates.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function template_include( string $template ): string {
		if ( is_admin() || is_embed() || 'theme' !== $this->page_layout() ) {
			return $template;
		}

		$type = TemplateTypes::detect();

		if ( '' === $type ) {
			return $template;
		}

		if ( in_array( $type, array( 'post', 'page' ), true ) && $this->is_self_designed() ) {
			return $template;
		}

		$id = $this->find( $type );

		if ( $id <= 0 ) {
			return $template;
		}

		$GLOBALS['breefer_template_id']   = $id;
		$GLOBALS['breefer_template_type'] = $type;

		$path = BREEFER_DIR . 'templates/theme/page.php';

		return is_readable( $path ) ? $path : $template;
	}

	/**
	 * Output the Breefer header and hide the theme's own.
	 *
	 * @param string|null $name Header variation.
	 * @return void
	 */
	public function classic_header( ?string $name ): void {
		$id = $this->region_id( 'header' );

		if ( $id <= 0 ) {
			return;
		}

		$this->emitted['header'] = true;

		$breefer_region_id = $id;

		require BREEFER_DIR . 'templates/theme/header.php';

		$this->discard_theme_part( 'header', $name );
	}

	/**
	 * Output the Breefer footer and hide the theme's own.
	 *
	 * @param string|null $name Footer variation.
	 * @return void
	 */
	public function classic_footer( ?string $name ): void {
		$id = $this->region_id( 'footer' );

		if ( $id <= 0 ) {
			return;
		}

		$this->emitted['footer'] = true;

		$breefer_region_id = $id;

		require BREEFER_DIR . 'templates/theme/footer.php';

		$this->discard_theme_part( 'footer', $name );
	}

	/**
	 * Load the theme template into a discarded buffer.
	 *
	 * get_header()/get_footer() use require_once, so having the file "already
	 * loaded" stops the theme from printing it a second time.
	 *
	 * @param string      $part Header or footer.
	 * @param string|null $name Variation.
	 * @return void
	 */
	private function discard_theme_part( string $part, ?string $name ): void {
		$templates = array();
		$name      = (string) $name;

		if ( '' !== $name ) {
			$templates[] = "{$part}-{$name}.php";
		}

		$templates[] = "{$part}.php";

		ob_start();
		locate_template( $templates, true, true );
		ob_end_clean();
	}

	/**
	 * Replace the header/footer template-part block on block themes.
	 *
	 * @param string|null          $pre   Pre-rendered content.
	 * @param array<string, mixed> $block Parsed block.
	 * @return string|null
	 */
	public function block_part( ?string $pre, array $block ): ?string {
		if ( null !== $pre || is_admin() || 'core/template-part' !== ( $block['blockName'] ?? '' ) ) {
			return $pre;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$slug  = isset( $attrs['slug'] ) ? (string) $attrs['slug'] : '';
		$area  = isset( $attrs['area'] ) ? (string) $attrs['area'] : '';
		$part  = '';

		if ( 'header' === $slug || 'header' === $area ) {
			$part = 'header';
		} elseif ( 'footer' === $slug || 'footer' === $area ) {
			$part = 'footer';
		}

		if ( '' === $part ) {
			return $pre;
		}

		$id = $this->region_id( $part );

		if ( $id <= 0 ) {
			return $pre;
		}

		$html = $this->renderer->render_post( $id );

		if ( '' === $html ) {
			return $pre;
		}

		return '<' . $part . ' class="breefer-theme-' . $part . ' wp-block-template-part">' . $html . '</' . $part . '>';
	}

	/**
	 * Region template for the request, with guards against admin/preview screens.
	 *
	 * @param string $part header|footer.
	 * @return int
	 */
	private function region_id( string $part ): int {
		if ( is_admin() || isset( $this->emitted[ $part ] ) || isset( $_GET['breefer_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 0;
		}

		// The blank canvas layout deliberately removes the site header and footer.
		if ( 'canvas' === $this->page_layout() ) {
			return 0;
		}

		return $this->find( $part );
	}

	/**
	 * Add body classes for the active theme-builder regions.
	 *
	 * @param array<int, string> $classes Classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( ! is_admin() ) {
			if ( $this->find( 'header' ) > 0 ) {
				$classes[] = 'breefer-has-header';
			}

			if ( $this->find( 'footer' ) > 0 ) {
				$classes[] = 'breefer-has-footer';
			}

			if ( ! empty( $GLOBALS['breefer_template_type'] ) ) {
				$classes[] = 'breefer-template-' . sanitize_html_class( (string) $GLOBALS['breefer_template_type'] );
			}
		}

		return $classes;
	}

	/**
	 * Load assets early when a theme-builder region is active.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		$body = TemplateTypes::detect();

		if ( $this->find( 'header' ) > 0 || $this->find( 'footer' ) > 0 || ( '' !== $body && $this->find( $body ) > 0 ) ) {
			FrontendAssets::enqueue();
		}
	}
}
