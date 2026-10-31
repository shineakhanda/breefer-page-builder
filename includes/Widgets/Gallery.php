<?php
/**
 * Image gallery and logo marquee widgets.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * "gallery" renders a responsive image grid; "marquee" a scrolling logo strip.
 */
final class Gallery extends AbstractWidget {

	/**
	 * Either "gallery" or "marquee".
	 */
	private string $mode;

	/**
	 * Constructor.
	 *
	 * @param string $mode gallery|marquee.
	 */
	public function __construct( string $mode = 'gallery' ) {
		$this->mode = 'marquee' === $mode ? 'marquee' : 'gallery';
	}

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return $this->mode;
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

		$images = array();

		foreach ( $this->lines( $settings, 'images' ) as $line ) {
			$parts = $this->parts( $line, 2 );
			$url   = esc_url( $parts[0] );

			if ( '' !== $url ) {
				$images[] = array(
					'url'     => $url,
					'caption' => $parts[1],
				);
			}
		}

		if ( array() === $images ) {
			return $this->placeholder(
				'marquee' === $this->mode ? __( 'Logo marquee', 'breefer' ) : __( 'Gallery', 'breefer' ),
				__( 'Add one image URL per line.', 'breefer' )
			);
		}

		return 'marquee' === $this->mode ? $this->marquee( $settings, $images ) : $this->grid( $settings, $images );
	}

	/**
	 * Responsive grid.
	 *
	 * @param array<string, mixed>                          $settings Settings.
	 * @param array<int, array{url: string, caption: string}> $images   Images.
	 * @return string
	 */
	private function grid( array $settings, array $images ): string {
		$columns = $this->int( $settings, 'columns', 1, 8, 3 );
		$link    = $this->bool( $settings, 'linkToFile' );
		$ratio   = $this->choice( $settings, 'ratio', array( 'auto', '1 / 1', '4 / 3', '3 / 2', '16 / 9' ), '1 / 1' );

		$vars = $this->vars(
			array(
				'--br-gallery-cols'   => (string) $columns,
				'--br-gallery-gap'    => $this->px( $settings, 'gap', 0, 80 ),
				'--br-gallery-radius' => $this->px( $settings, 'radius', 0, 80 ),
				'--br-gallery-ratio'  => 'auto' === $ratio ? '' : $ratio,
			)
		);

		$html = '<div class="br-gallery"' . $vars . '>';

		foreach ( $images as $image ) {
			$img = '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['caption'] ) . '" loading="lazy" decoding="async">';

			if ( $link ) {
				$img = '<a href="' . esc_url( $image['url'] ) . '" target="_blank" rel="noopener noreferrer">' . $img . '</a>';
			}

			$html .= '<figure class="br-gallery-item">' . $img
				. ( '' !== $image['caption'] ? '<figcaption>' . esc_html( $image['caption'] ) . '</figcaption>' : '' )
				. '</figure>';
		}

		return $html . '</div>';
	}

	/**
	 * Infinite horizontal logo strip. Pauses on hover and respects reduced motion.
	 *
	 * @param array<string, mixed>                          $settings Settings.
	 * @param array<int, array{url: string, caption: string}> $images   Images.
	 * @return string
	 */
	private function marquee( array $settings, array $images ): string {
		$vars = $this->vars(
			array(
				'--br-marquee-height'   => $this->px( $settings, 'logoHeight', 16, 200 ),
				'--br-marquee-gap'      => $this->px( $settings, 'gap', 8, 200 ),
				'--br-marquee-duration' => $this->int( $settings, 'speed', 5, 240, 30 ) . 's',
			)
		);

		$strip = '';

		foreach ( $images as $image ) {
			$strip .= '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $image['caption'] ) . '" loading="lazy" decoding="async">';
		}

		// The strip is duplicated for a seamless loop; the copy is hidden from assistive tech.
		return '<div class="br-marquee"' . $vars . '><div class="br-marquee-track"><div class="br-marquee-set">' . $strip
			. '</div><div class="br-marquee-set" aria-hidden="true">' . $strip . '</div></div></div>';
	}
}
