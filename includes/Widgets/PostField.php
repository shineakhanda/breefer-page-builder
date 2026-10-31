<?php
/**
 * Dynamic post field widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs a field of the current post: title, content, image, author, date...
 * Used by single-post and page templates built in the theme builder.
 */
final class PostField extends AbstractWidget {

	/**
	 * Guards against a post's content embedding a template that renders it again.
	 */
	private static int $depth = 0;

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'postfield';
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

		$post = Context::post();

		if ( null === $post ) {
			return '';
		}

		$field = $this->choice(
			$settings,
			'field',
			array( 'title', 'content', 'excerpt', 'featured_image', 'author', 'date', 'modified', 'comments', 'categories', 'tags', 'readmore' ),
			'title'
		);

		$link = $this->bool( $settings, 'link' );

		switch ( $field ) {
			case 'content':
				if ( self::$depth > 1 ) {
					return '';
				}

				++self::$depth;
				$html = (string) apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				--self::$depth;

				return '<div class="br-post-content entry-content">' . $html . '</div>';

			case 'excerpt':
				return '<div class="br-post-excerpt">' . wp_kses_post( wpautop( get_the_excerpt( $post ) ) ) . '</div>';

			case 'featured_image':
				$size  = $this->choice( $settings, 'imageSize', array( 'thumbnail', 'medium', 'large', 'full' ), 'large' );
				$image = get_the_post_thumbnail( $post, $size );

				if ( '' === $image ) {
					return Context::is_preview() ? $this->placeholder( __( 'Featured image', 'breefer' ) ) : '';
				}

				return '<div class="br-post-image">' . ( $link ? '<a href="' . esc_url( (string) get_permalink( $post ) ) . '">' . $image . '</a>' : $image ) . '</div>';

			case 'author':
				$author = (int) $post->post_author;
				$name   = get_the_author_meta( 'display_name', $author );
				$avatar = $this->bool( $settings, 'showAvatar' ) ? get_avatar( $author, 40 ) : '';

				return '<div class="br-post-author">' . $avatar . '<span>' . esc_html( (string) $name ) . '</span></div>';

			case 'date':
			case 'modified':
				$format = $this->str( $settings, 'dateFormat' );
				$date   = 'date' === $field
					? get_the_date( $format, $post )
					: get_the_modified_date( $format, $post );

				return '<time class="br-post-date" datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( (string) $date ) . '</time>';

			case 'comments':
				return '<span class="br-post-comments">' . esc_html(
					sprintf(
						/* translators: %s: comment count. */
						_n( '%s comment', '%s comments', (int) $post->comment_count, 'breefer' ),
						number_format_i18n( (int) $post->comment_count )
					)
				) . '</span>';

			case 'categories':
			case 'tags':
				$taxonomy = 'categories' === $field ? 'category' : 'post_tag';
				$terms    = get_the_terms( $post, $taxonomy );

				if ( ! is_array( $terms ) || array() === $terms ) {
					return '';
				}

				$items = '';

				foreach ( $terms as $term ) {
					$url = get_term_link( $term );

					$items .= '<li>' . ( is_string( $url ) ? '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>' : esc_html( $term->name ) ) . '</li>';
				}

				return '<ul class="br-post-terms">' . $items . '</ul>';

			case 'readmore':
				return $this->link_button( $this->str( $settings, 'buttonText', __( 'Read more', 'breefer' ) ), (string) get_permalink( $post ) );

			case 'title':
			default:
				$tag   = $this->choice( $settings, 'tag', array( 'h1', 'h2', 'h3', 'h4', 'p', 'div' ), 'h1' );
				$title = esc_html( get_the_title( $post ) );

				if ( $link ) {
					$title = '<a href="' . esc_url( (string) get_permalink( $post ) ) . '">' . $title . '</a>';
				}

				return '<' . $tag . ' class="br-post-title">' . $title . '</' . $tag . '>';
		}
	}
}
