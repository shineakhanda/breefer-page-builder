<?php
/**
 * Theme builder admin screen.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\PostTypes\Templates;
use Breefer\Theme\TemplateTypes;
use Breefer\Utils\Capabilities;
use Breefer\Utils\View;

defined( 'ABSPATH' ) || exit;

/**
 * Lists every template role with its templates and a one-click "add" action.
 */
final class ThemeBuilderPage {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add the submenu entry.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_submenu_page(
			'breefer',
			__( 'Theme Builder', 'breefer' ),
			__( 'Theme Builder', 'breefer' ),
			Capabilities::MANAGE,
			'breefer-theme',
			array( $this, 'render' ),
			2
		);
	}

	/**
	 * Load the shared admin styles on this screen.
	 *
	 * @param string $hook_suffix Screen hook.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( 'breefer_page_breefer-theme' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'breefer-tokens',
			BREEFER_URL . 'assets/css/tokens.css',
			array(),
			BREEFER_VERSION
		);
		wp_enqueue_style(
			'breefer-admin',
			BREEFER_URL . 'assets/css/admin.css',
			array( 'breefer-tokens', 'dashicons' ),
			BREEFER_VERSION
		);
		wp_add_inline_style(
			'breefer-tokens',
			\Breefer\Utils\Palette::css( (string) \Breefer\Utils\Options::get( 'appearance.palette', 'violet' ) )
		);
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'breefer' ),
				esc_html__( 'Forbidden', 'breefer' ),
				array( 'response' => 403 )
			);
		}

		$posts = get_posts(
			array(
				'post_type'        => Templates::TYPE,
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'numberposts'      => 200,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);

		$by_type = array();

		foreach ( $posts as $post ) {
			$type = (string) get_post_meta( $post->ID, TemplateTypes::META, true );
			$cond = (string) get_post_meta( $post->ID, TemplateTypes::CONDITION, true );

			$by_type[ $type ][] = array(
				'title'     => get_the_title( $post ),
				'edit_url'  => admin_url( 'admin.php?page=breefer-editor&id=' . $post->ID ),
				'condition' => TemplateTypes::conditions()[ '' === $cond ? 'all' : $cond ] ?? '',
			);
		}

		View::render(
			'admin/theme-builder',
			array(
				'types'   => TemplateTypes::available(),
				'by_type' => $by_type,
				'add_url' => admin_url( 'admin.php?page=breefer-editor&new=1&type=template&template_type=' ),
			)
		);
	}
}
