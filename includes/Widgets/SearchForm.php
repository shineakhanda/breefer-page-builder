<?php
/**
 * Search form widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Site or product search.
 */
final class SearchForm extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'searchform';
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
		unset( $children_html );

		$uid       = 'br-search-' . preg_replace( '/[^a-z0-9]/', '', strtolower( $node_id ) );
		$type      = $this->choice( $settings, 'postType', array( 'all', 'post', 'page', 'product' ), 'all' );
		$show_btn  = ! $this->bool( $settings, 'hideButton' );
		$style     = $this->choice( $settings, 'style', array( 'pill', 'rounded', 'underline' ), 'pill' );
		$value     = is_search() ? (string) get_search_query( false ) : '';
		$vars      = $this->vars(
			array(
				'--br-search-accent' => $this->color( $settings, 'buttonColor' ),
				'--br-search-bg'     => $this->color( $settings, 'fieldBackground' ),
			)
		);

		$html  = '<form role="search" method="get" class="br-search is-' . esc_attr( $style ) . '" action="' . esc_url( home_url( '/' ) ) . '"' . $vars . '>';
		$html .= '<label class="screen-reader-text" for="' . esc_attr( $uid ) . '">' . esc_html__( 'Search', 'breefer' ) . '</label>';
		$html .= '<input id="' . esc_attr( $uid ) . '" type="search" name="s" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $this->str( $settings, 'placeholder', __( 'Search…', 'breefer' ) ) ) . '">';

		if ( 'all' !== $type ) {
			$html .= '<input type="hidden" name="post_type" value="' . esc_attr( $type ) . '">';
		}

		if ( $show_btn ) {
			$html .= '<button type="submit" class="br-w-btn">' . esc_html( $this->str( $settings, 'buttonText', __( 'Search', 'breefer' ) ) ) . '</button>';
		}

		return $html . '</form>';
	}
}
