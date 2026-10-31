<?php
/**
 * Archive title widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Title (and optional description) for archives, search, the shop and 404 pages.
 */
final class ArchiveTitle extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'archivetitle';
	}

	/**
	 * Render.
	 *
	 * @param array<string, mixed> $settings      Settings.
	 * @param string               $children_html Children.
	 * @param string               $node_id       Node id.
	 * @return string
	 */
	public function render( array $settings, string $children_html, string $node_id ): string {
		unset( $children_html, $node_id );

		$tag   = $this->choice( $settings, 'tag', array( 'h1', 'h2', 'h3', 'p', 'div' ), 'h1' );
		$title = $this->title( $this->bool( $settings, 'keepPrefix' ) );
		$html  = '<' . $tag . ' class="br-archive-title">' . esc_html( $title ) . '</' . $tag . '>';

		if ( $this->bool( $settings, 'showDescription' ) ) {
			$description = $this->description();

			$html .= '' !== $description ? '<div class="br-archive-description">' . wp_kses_post( $description ) . '</div>' : '';
		}

		return $html;
	}

	/**
	 * Resolve the title for the current request.
	 *
	 * @param bool $keep_prefix Keep "Category:"-style prefixes.
	 * @return string
	 */
	private function title( bool $keep_prefix ): string {
		if ( Context::is_preview() ) {
			return __( 'Archive title', 'breefer' );
		}

		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'woocommerce_page_title' ) ) {
			return (string) woocommerce_page_title( false );
		}

		if ( is_search() ) {
			/* translators: %s: search term. */
			return sprintf( __( 'Search results for "%s"', 'breefer' ), get_search_query( false ) );
		}

		if ( is_404() ) {
			return __( 'Page not found', 'breefer' );
		}

		if ( is_home() && ! is_front_page() ) {
			return (string) get_the_title( (int) get_option( 'page_for_posts' ) );
		}

		if ( is_archive() ) {
			if ( ! $keep_prefix ) {
				add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
			}

			$title = wp_strip_all_tags( get_the_archive_title() );

			if ( ! $keep_prefix ) {
				remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
			}

			return $title;
		}

		$post = Context::post();

		return null !== $post ? get_the_title( $post ) : get_bloginfo( 'name' );
	}

	/**
	 * Archive description, if any.
	 *
	 * @return string
	 */
	private function description(): string {
		if ( Context::is_preview() ) {
			return __( 'The archive description appears here.', 'breefer' );
		}

		if ( is_archive() || is_search() ) {
			return (string) get_the_archive_description();
		}

		return '';
	}
}
