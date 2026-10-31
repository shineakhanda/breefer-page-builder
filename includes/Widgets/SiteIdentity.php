<?php
/**
 * Site logo and site title widgets.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * "sitelogo" renders the custom logo (or the site name); "sitetitle" the name and tagline.
 */
final class SiteIdentity extends AbstractWidget {

	/**
	 * Either "sitelogo" or "sitetitle".
	 */
	private string $mode;

	/**
	 * Constructor.
	 *
	 * @param string $mode sitelogo|sitetitle.
	 */
	public function __construct( string $mode = 'sitelogo' ) {
		$this->mode = 'sitetitle' === $mode ? 'sitetitle' : 'sitelogo';
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

		$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$link = $this->bool( $settings, 'noLink' ) ? '' : home_url( '/' );

		if ( 'sitetitle' === $this->mode ) {
			$tag  = $this->choice( $settings, 'tag', array( 'h1', 'h2', 'h3', 'p', 'div' ), 'p' );
			$html = '<' . $tag . ' class="br-site-title">' . $this->wrap_link( esc_html( $name ), $link ) . '</' . $tag . '>';

			if ( $this->bool( $settings, 'showTagline' ) ) {
				$html .= '<p class="br-site-tagline">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
			}

			return $html;
		}

		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$width   = $this->px( $settings, 'logoWidth', 24, 800 );
		$inner   = '';

		if ( $logo_id > 0 ) {
			$inner = (string) wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'br-site-logo-img', 'alt' => $name ) );
		}

		if ( '' === $inner ) {
			$inner = '<span class="br-site-logo-text">' . esc_html( $name ) . '</span>';
		}

		return '<div class="br-site-logo"' . $this->vars( array( '--br-logo-width' => $width ) ) . '>' . $this->wrap_link( $inner, $link ) . '</div>';
	}

	/**
	 * Wrap escaped markup in a link when a URL is provided.
	 *
	 * @param string $inner Escaped markup.
	 * @param string $url   URL or empty string.
	 * @return string
	 */
	private function wrap_link( string $inner, string $url ): string {
		return '' === $url ? $inner : '<a href="' . esc_url( $url ) . '" rel="home">' . $inner . '</a>';
	}
}
