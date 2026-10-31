<?php
/**
 * Video widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a privacy-friendly YouTube/Vimeo embed or a local video file.
 */
final class Video extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'video';
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

		$url = $this->str( $settings, 'url' );

		if ( '' === $url ) {
			return '';
		}

		$title = $this->str( $settings, 'title', __( 'Video', 'breefer' ) );
		$embed = $this->embed_url( $url );

		if ( '' !== $embed ) {
			return sprintf(
				'<div class="br-video"><iframe src="%1$s" title="%2$s" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',
				esc_url( $embed ),
				esc_attr( $title )
			);
		}

		if ( preg_match( '/\.(mp4|webm|ogg)(\?|$)/i', $url ) ) {
			return sprintf(
				'<div class="br-video"><video controls preload="metadata" src="%1$s" title="%2$s"></video></div>',
				esc_url( $url ),
				esc_attr( $title )
			);
		}

		return '<div class="br-video is-empty"><span>' . esc_html__( 'Unsupported video URL', 'breefer' ) . '</span></div>';
	}

	/**
	 * Convert a public URL to a privacy-friendly embed URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function embed_url( string $url ): string {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}

		$host  = strtolower( (string) $parts['host'] );
		$path  = isset( $parts['path'] ) ? (string) $parts['path'] : '';
		$query = array();

		if ( isset( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query );
		}

		if ( str_contains( $host, 'youtube.com' ) || str_contains( $host, 'youtu.be' ) ) {
			$id = isset( $query['v'] ) && is_string( $query['v'] ) ? $query['v'] : '';

			if ( '' === $id ) {
				$segments = array_values( array_filter( explode( '/', $path ), static fn( $segment ) => '' !== $segment ) );
				$candidate = end( $segments );

				if ( is_string( $candidate ) && 1 === preg_match( '/^[A-Za-z0-9_-]{6,}$/', $candidate ) ) {
					$id = $candidate;
				}
			}

			if ( '' !== $id ) {
				return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id );
			}
		}

		if ( str_contains( $host, 'vimeo.com' ) && preg_match( '~/(\d+)~', $path, $matches ) ) {
			return 'https://player.vimeo.com/video/' . $matches[1];
		}

		return '';
	}
}
