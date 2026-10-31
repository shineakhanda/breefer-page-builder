<?php
/**
 * Document renderer.
 *
 * @package Breefer
 */

namespace Breefer\Rendering;

use Breefer\Documents\Document;
use Breefer\Frontend\FrontendAssets;
use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a JSON element tree to semantic HTML plus scoped CSS.
 */
final class Renderer {

	/**
	 * Widget registry.
	 */
	private WidgetRegistry $registry;

	/**
	 * Style generator.
	 */
	private StyleGenerator $styles;

	/**
	 * Constructor.
	 *
	 * @param WidgetRegistry $registry Widget registry.
	 * @param StyleGenerator $styles   Style generator.
	 */
	public function __construct( WidgetRegistry $registry, StyleGenerator $styles ) {
		$this->registry = $registry;
		$this->styles   = $styles;
	}

	/**
	 * Render a document.
	 *
	 * @param array<string, mixed> $document Document.
	 * @return array{html: string, css: string}
	 */
	public function render_document( array $document ): array {
		$root = $document['root'] ?? null;

		if ( ! is_array( $root ) ) {
			return array(
				'html' => '',
				'css'  => '',
			);
		}

		$css  = '';
		$html = $this->render_node( $root, 0, $css );

		return array(
			'html' => $html,
			'css'  => $css,
		);
	}

	/**
	 * Render a post through Breefer.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public function render_post( int $post_id ): string {
		$document = Document::get( $post_id );
		$root     = $document['root'] ?? null;

		if ( ! is_array( $root ) || empty( $root['children'] ) ) {
			return '';
		}

		$previous = Context::set_document( $post_id );
		$result   = $this->render_document( $document );

		Context::set_document( $previous );

		/**
		 * Filter the rendered document payload.
		 *
		 * @param array $result   HTML and CSS.
		 * @param array $document Document data.
		 * @param int   $post_id  Post id.
		 */
		$result = (array) apply_filters( 'breefer/render/document', $result, $document, $post_id );

		$html = isset( $result['html'] ) ? (string) $result['html'] : '';
		$css  = isset( $result['css'] ) ? (string) $result['css'] : '';

		if ( '' === $html ) {
			return '';
		}

		FrontendAssets::enqueue();

		$output = '';

		if ( '' !== $css ) {
			$output .= '<style class="breefer-styles">' . $this->minify( $css ) . '</style>';
		}

		$output .= '<div class="breefer-canvas" data-breefer-id="' . esc_attr( (string) $post_id ) . '">';
		$output .= $html;
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render one node.
	 *
	 * @param array<string, mixed> $node  Node.
	 * @param int                  $depth Depth.
	 * @param string               $css   CSS accumulator.
	 * @return string
	 */
	private function render_node( array $node, int $depth, string &$css ): string {
		if ( $depth > 20 ) {
			return '';
		}

		$id   = isset( $node['id'] ) ? sanitize_html_class( (string) $node['id'] ) : '';
		$type = isset( $node['type'] ) ? sanitize_key( (string) $node['type'] ) : '';

		if ( '' === $id || '' === $type || ! $this->registry->has( $type ) ) {
			return '';
		}

		$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
		$children = '';

		if ( ! $this->is_visible( $settings ) ) {
			return '';
		}

		if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
			foreach ( $node['children'] as $child ) {
				if ( is_array( $child ) ) {
					$children .= $this->render_node( $child, $depth + 1, $css );
				}
			}
		}

		$inner = $this->registry->render( $type, $settings, $children, $id );

		if ( '' === $inner && 'container' !== $type ) {
			return '';
		}

		$css .= $this->styles->for_node( $node );

		return sprintf(
			'<div class="br-node br-%1$s br-node-%2$s%4$s" data-br-id="%2$s"%5$s>%3$s</div>',
			esc_attr( $type ),
			esc_attr( $id ),
			$inner,
			esc_attr( $this->extra_classes( $settings ) ),
			$this->extra_attributes( $settings )
		);
	}

	/**
	 * Conditional visibility (logged in / logged out).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	private function is_visible( array $settings ): bool {
		$rule = isset( $settings['visibility'] ) && is_string( $settings['visibility'] ) ? $settings['visibility'] : 'all';

		if ( 'loggedin' === $rule ) {
			return is_user_logged_in();
		}

		if ( 'loggedout' === $rule ) {
			return ! is_user_logged_in();
		}

		return true;
	}

	/**
	 * Author supplied and animation classes.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function extra_classes( array $settings ): string {
		$classes = array();

		if ( isset( $settings['cssClass'] ) && is_string( $settings['cssClass'] ) ) {
			foreach ( preg_split( '/\s+/', trim( $settings['cssClass'] ) ) ?: array() as $class ) {
				$class = sanitize_html_class( $class );

				if ( '' !== $class ) {
					$classes[] = $class;
				}
			}
		}

		$animation = isset( $settings['animation'] ) && is_string( $settings['animation'] ) ? $settings['animation'] : '';

		if ( in_array( $animation, array( 'fade', 'fadeup', 'fadedown', 'fadeleft', 'faderight', 'zoom', 'slideup' ), true ) ) {
			$classes[] = 'br-anim';
			$classes[] = 'br-anim-' . $animation;
		}

		return array() === $classes ? '' : ' ' . implode( ' ', $classes );
	}

	/**
	 * Anchor id and animation timing attributes.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	private function extra_attributes( array $settings ): string {
		$attrs = '';

		if ( isset( $settings['anchorId'] ) && is_string( $settings['anchorId'] ) ) {
			$anchor = sanitize_html_class( $settings['anchorId'] );

			if ( '' !== $anchor ) {
				$attrs .= ' id="' . esc_attr( $anchor ) . '"';
			}
		}

		if ( isset( $settings['animationDelay'] ) && is_numeric( $settings['animationDelay'] ) ) {
			$attrs .= ' style="--br-anim-delay:' . (int) max( 0, min( 3000, (int) $settings['animationDelay'] ) ) . 'ms"';
		}

		return $attrs;
	}

	/**
	 * Collapse whitespace in generated CSS.
	 *
	 * @param string $css CSS.
	 * @return string
	 */
	private function minify( string $css ): string {
		return (string) preg_replace( '/\s+/', ' ', str_replace( '; ', ';', trim( $css ) ) );
	}
}
