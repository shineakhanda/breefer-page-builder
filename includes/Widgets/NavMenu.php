<?php
/**
 * Navigation menu widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a WordPress menu with a responsive toggle.
 */
final class NavMenu extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'navmenu';
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

		$menu = $this->resolve_menu( $this->str( $settings, 'menu' ) );

		if ( null === $menu ) {
			return $this->placeholder( __( 'Menu', 'breefer' ), __( 'Create a menu under Appearance → Menus, then pick it here.', 'breefer' ) );
		}

		$uid    = 'br-nav-' . preg_replace( '/[^a-z0-9]/', '', strtolower( $node_id ) );
		$layout = $this->choice( $settings, 'layout', array( 'horizontal', 'vertical' ), 'horizontal' );

		$items = wp_nav_menu(
			array(
				'menu'        => $menu,
				'container'   => false,
				'menu_class'  => 'br-menu',
				'menu_id'     => $uid . '-list',
				'echo'        => false,
				'fallback_cb' => false,
				'depth'       => $this->int( $settings, 'depth', 1, 4, 2 ),
			)
		);

		if ( ! is_string( $items ) || '' === $items ) {
			return '';
		}

		$vars = $this->vars(
			array(
				'--br-nav-color'  => $this->color( $settings, 'linkColor' ),
				'--br-nav-hover'  => $this->color( $settings, 'hoverColor' ),
				'--br-nav-menu'   => $this->color( $settings, 'dropdownBackground' ),
				'--br-nav-gap'    => $this->px( $settings, 'gap', 0, 80 ),
				'--br-nav-size'   => $this->px( $settings, 'fontSize', 10, 32 ),
			)
		);

		$html  = '<nav class="br-nav is-' . esc_attr( $layout ) . '" data-breefer-nav aria-label="' . esc_attr( $this->str( $settings, 'label', __( 'Menu', 'breefer' ) ) ) . '"' . $vars . '>';
		$html .= '<button type="button" class="br-nav-toggle" aria-expanded="false" aria-controls="' . esc_attr( $uid . '-list' ) . '">';
		$html .= '<span class="br-nav-toggle-bars" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Toggle menu', 'breefer' ) . '</span></button>';

		return $html . $items . '</nav>';
	}

	/**
	 * Resolve a menu by id/slug, falling back to the first menu.
	 *
	 * @param string $value Stored value.
	 * @return int|null Menu term id.
	 */
	private function resolve_menu( string $value ): ?int {
		if ( '' !== $value ) {
			$menu = wp_get_nav_menu_object( ctype_digit( $value ) ? (int) $value : $value );

			if ( $menu instanceof \WP_Term ) {
				return (int) $menu->term_id;
			}
		}

		$menus = wp_get_nav_menus();

		return array() !== $menus ? (int) $menus[0]->term_id : null;
	}
}
