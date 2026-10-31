<?php
/**
 * Breadcrumbs widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Accessible breadcrumb trail with BreadcrumbList structured data.
 * Understands posts, pages, taxonomies and WooCommerce products.
 */
final class Breadcrumbs extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'breadcrumbs';
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

		$trail = $this->trail( $this->str( $settings, 'homeLabel', __( 'Home', 'breefer' ) ) );

		if ( count( $trail ) < 2 ) {
			return '';
		}

		$separator = $this->choice( $settings, 'separator', array( '/', '›', '»', '→', '·' ), '›' );
		$last      = count( $trail ) - 1;
		$html      = '<nav class="br-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'breefer' ) . '"' . $this->vars( array( '--br-bc-sep' => '"' . $separator . '"' ) ) . '><ol>';
		$schema    = array();

		foreach ( $trail as $index => $crumb ) {
			if ( $index === $last || '' === $crumb['url'] ) {
				$html .= '<li aria-current="page"><span>' . esc_html( $crumb['label'] ) . '</span></li>';
			} else {
				$html .= '<li><a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a></li>';
			}

			$schema[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['label'],
				'item'     => '' !== $crumb['url'] ? $crumb['url'] : null,
			);
		}

		$html .= '</ol>';

		if ( ! Context::is_preview() && ! $this->bool( $settings, 'noSchema' ) ) {
			$json = wp_json_encode(
				array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => array_map( static fn( array $item ): array => array_filter( $item, static fn( $value ): bool => null !== $value ), $schema ),
				),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);

			if ( is_string( $json ) ) {
				$html .= '<script type="application/ld+json">' . $json . '</script>';
			}
		}

		return $html . '</nav>';
	}

	/**
	 * Build the trail for the current request.
	 *
	 * @param string $home_label Label for the first crumb.
	 * @return array<int, array{label: string, url: string}>
	 */
	private function trail( string $home_label ): array {
		$trail = array(
			array(
				'label' => $home_label,
				'url'   => home_url( '/' ),
			),
		);

		if ( Context::is_preview() ) {
			$post = Context::post();

			if ( null !== $post ) {
				$trail[] = array(
					'label' => __( 'Category', 'breefer' ),
					'url'   => home_url( '/' ),
				);
				$trail[] = array(
					'label' => get_the_title( $post ),
					'url'   => '',
				);
			}

			return $trail;
		}

		if ( is_singular() ) {
			$post = Context::post();

			if ( null === $post ) {
				return $trail;
			}

			$taxonomy = 'product' === $post->post_type ? 'product_cat' : ( 'post' === $post->post_type ? 'category' : '' );

			if ( 'product' === $post->post_type && function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) {
				$trail[] = array(
					'label' => get_the_title( wc_get_page_id( 'shop' ) ),
					'url'   => (string) get_permalink( wc_get_page_id( 'shop' ) ),
				);
			} elseif ( 'page' !== $post->post_type && 'post' !== $post->post_type ) {
				$archive = get_post_type_archive_link( $post->post_type );
				$object  = get_post_type_object( $post->post_type );

				if ( is_string( $archive ) && null !== $object ) {
					$trail[] = array(
						'label' => $object->labels->name,
						'url'   => $archive,
					);
				}
			}

			if ( 'page' === $post->post_type ) {
				foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
					$trail[] = array(
						'label' => get_the_title( (int) $ancestor ),
						'url'   => (string) get_permalink( (int) $ancestor ),
					);
				}
			} elseif ( '' !== $taxonomy ) {
				$terms = get_the_terms( $post, $taxonomy );

				if ( is_array( $terms ) && array() !== $terms ) {
					$term = reset( $terms );

					foreach ( array_reverse( get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
						$ancestor = get_term( (int) $ancestor_id, $taxonomy );

						if ( $ancestor instanceof \WP_Term ) {
							$trail[] = $this->term_crumb( $ancestor );
						}
					}

					$trail[] = $this->term_crumb( $term );
				}
			}

			$trail[] = array(
				'label' => get_the_title( $post ),
				'url'   => '',
			);

			return $trail;
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();

			if ( $term instanceof \WP_Term ) {
				if ( 'product_cat' === $term->taxonomy && function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) {
					$trail[] = array(
						'label' => get_the_title( wc_get_page_id( 'shop' ) ),
						'url'   => (string) get_permalink( wc_get_page_id( 'shop' ) ),
					);
				}

				foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
					$ancestor = get_term( (int) $ancestor_id, $term->taxonomy );

					if ( $ancestor instanceof \WP_Term ) {
						$trail[] = $this->term_crumb( $ancestor );
					}
				}

				$trail[] = array(
					'label' => $term->name,
					'url'   => '',
				);
			}

			return $trail;
		}

		if ( is_search() ) {
			$trail[] = array(
				/* translators: %s: search term. */
				'label' => sprintf( __( 'Results for "%s"', 'breefer' ), get_search_query( false ) ),
				'url'   => '',
			);
		} elseif ( is_404() ) {
			$trail[] = array(
				'label' => __( 'Page not found', 'breefer' ),
				'url'   => '',
			);
		} elseif ( is_archive() ) {
			$trail[] = array(
				'label' => wp_strip_all_tags( get_the_archive_title() ),
				'url'   => '',
			);
		}

		return $trail;
	}

	/**
	 * Crumb for a term.
	 *
	 * @param \WP_Term $term Term.
	 * @return array{label: string, url: string}
	 */
	private function term_crumb( \WP_Term $term ): array {
		$url = get_term_link( $term );

		return array(
			'label' => $term->name,
			'url'   => is_string( $url ) ? $url : '',
		);
	}
}
