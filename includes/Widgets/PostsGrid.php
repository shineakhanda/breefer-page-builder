<?php
/**
 * Posts grid widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Context;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Query-driven card grid. In "main query" mode it lists the current archive,
 * search results or blog page with pagination, for archive templates.
 */
final class PostsGrid extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'postsgrid';
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

		$use_main = 'main' === $this->str( $settings, 'queryMode', 'custom' ) && ! Context::is_preview() && ( is_archive() || is_search() || is_home() );
		$query    = null;
		$posts    = array();
		$pager    = '';

		if ( $use_main ) {
			global $wp_query;

			$posts = is_array( $wp_query->posts ) ? $wp_query->posts : array();
			$pager = $this->pagination( (int) $wp_query->max_num_pages );
		} else {
			$query = new WP_Query( $this->query_args( $settings ) );
			$posts = $query->posts;
		}

		if ( array() === $posts ) {
			$empty = Context::is_preview()
				? $this->placeholder( __( 'Posts grid', 'breefer' ), __( 'No posts found yet.', 'breefer' ) )
				: '<p class="br-empty">' . esc_html( $this->str( $settings, 'emptyText', __( 'Nothing found.', 'breefer' ) ) ) . '</p>';

			return $empty;
		}

		$columns = $this->int( $settings, 'columns', 1, 6, 3 );
		$layout  = $this->choice( $settings, 'layout', array( 'grid', 'list' ), 'grid' );
		$vars    = $this->vars(
			array(
				'--br-posts-cols'   => (string) $columns,
				'--br-posts-gap'    => $this->px( $settings, 'gap', 0, 80 ),
				'--br-posts-ratio'  => $this->choice( $settings, 'imageRatio', array( '16 / 9', '4 / 3', '1 / 1', '3 / 2' ), '16 / 9' ),
				'--br-posts-bg'     => $this->color( $settings, 'cardBackground' ),
				'--br-posts-accent' => $this->color( $settings, 'accent' ),
			)
		);

		$html = '<div class="br-posts is-' . esc_attr( $layout ) . '"' . $vars . '>';

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$html .= $this->card( $post, $settings );
			}
		}

		$html .= '</div>' . $pager;

		return $html;
	}

	/**
	 * Build the WP_Query arguments from settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	private function query_args( array $settings ): array {
		$type = sanitize_key( $this->str( $settings, 'postType', 'post' ) );

		if ( ! post_type_exists( $type ) ) {
			$type = 'post';
		}

		$args = array(
			'post_type'           => $type,
			'post_status'         => 'publish',
			'posts_per_page'      => $this->int( $settings, 'count', 1, 48, 6 ),
			'orderby'             => $this->choice( $settings, 'orderby', array( 'date', 'title', 'modified', 'rand', 'comment_count', 'menu_order' ), 'date' ),
			'order'               => 'ASC' === strtoupper( $this->str( $settings, 'order', 'DESC' ) ) ? 'ASC' : 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		$term = sanitize_title( $this->str( $settings, 'category' ) );

		if ( '' !== $term ) {
			$taxonomy = 'product' === $type ? 'product_cat' : 'category';

			if ( taxonomy_exists( $taxonomy ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => array( $term ),
					),
				);
			}
		}

		// Never list the page that is rendering the grid inside itself.
		$current = Context::document();

		if ( $current > 0 ) {
			$args['post__not_in'] = array( $current ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		return $args;
	}

	/**
	 * One card.
	 *
	 * @param WP_Post              $post     Post.
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function card( WP_Post $post, array $settings ): string {
		$url   = (string) get_permalink( $post );
		$title = get_the_title( $post );
		$html  = '<article class="br-post-card">';

		if ( ! $this->bool( $settings, 'hideImage' ) ) {
			$image = get_the_post_thumbnail( $post, 'large', array( 'loading' => 'lazy' ) );

			if ( '' !== $image ) {
				$html .= '<a class="br-post-thumb" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . $image . '</a>';
			}
		}

		$html .= '<div class="br-post-body">';

		if ( ! $this->bool( $settings, 'hideMeta' ) ) {
			$html .= '<div class="br-post-meta"><time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( (string) get_the_date( '', $post ) ) . '</time></div>';
		}

		$html .= '<h3 class="br-post-title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';

		if ( ! $this->bool( $settings, 'hideExcerpt' ) ) {
			$length  = $this->int( $settings, 'excerptLength', 5, 100, 22 );
			$excerpt = wp_trim_words( has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $length );

			$html .= '' !== $excerpt ? '<p class="br-post-excerpt">' . esc_html( $excerpt ) . '</p>' : '';
		}

		if ( $this->bool( $settings, 'showButton' ) ) {
			$html .= $this->link_button( $this->str( $settings, 'buttonText', __( 'Read more', 'breefer' ) ), $url, false, 'is-small' );
		}

		return $html . '</div></article>';
	}

	/**
	 * Numbered pagination for the main query.
	 *
	 * @param int $total Total pages.
	 * @return string
	 */
	private function pagination( int $total ): string {
		if ( $total < 2 ) {
			return '';
		}

		$links = paginate_links(
			array(
				'total'     => $total,
				'current'   => max( 1, (int) get_query_var( 'paged' ) ),
				'type'      => 'list',
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
			)
		);

		return is_string( $links ) ? '<nav class="br-pagination" aria-label="' . esc_attr__( 'Pagination', 'breefer' ) . '">' . $links . '</nav>' : '';
	}
}
