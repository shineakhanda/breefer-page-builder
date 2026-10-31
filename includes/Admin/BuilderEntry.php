<?php
/**
 * Default builder entry points.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\Documents\Document;
use Breefer\Utils\Options;
use WP_Admin_Bar;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Makes the builder reachable from the admin bar, list tables and Add New.
 */
final class BuilderEntry {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'admin_init', array( $this, 'maybe_create_page' ) );
		add_filter( 'page_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'load-post-new.php', array( $this, 'maybe_default_editor' ) );
	}

	/**
	 * Handle the New page menu route before output.
	 *
	 * @return void
	 */
	public function maybe_create_page(): void {
		if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( 'breefer-new-page' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		self::create_page_redirect();
	}

	/**
	 * Create a draft document.
	 *
	 * @param string $type    page|post|breefer_template.
	 * @param string $title   Title.
	 * @param bool   $enabled Enable the builder for pages/posts.
	 * @return int
	 */
	public static function create_document( string $type, string $title, bool $enabled = true, string $template_type = '' ): int {
		if ( ! in_array( $type, array( 'page', 'post', 'breefer_template' ), true ) ) {
			return 0;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => $type,
				'post_status' => 'draft',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return 0;
		}

		Document::save( (int) $id, Document::seed_document() );

		if ( 'breefer_template' === $type && \Breefer\Theme\TemplateTypes::exists( $template_type ) ) {
			update_post_meta( (int) $id, \Breefer\Theme\TemplateTypes::META, $template_type );
		}

		if ( $enabled && in_array( $type, array( 'page', 'post' ), true ) ) {
			Document::set_enabled( (int) $id, true );
		}

		return (int) $id;
	}

	/**
	 * Create a page from the admin menu and open the builder.
	 *
	 * @return void
	 */
	public static function create_page_redirect(): void {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to create pages.', 'breefer' ),
				esc_html__( 'Forbidden', 'breefer' ),
				array( 'response' => 403 )
			);
		}

		$id = self::create_document( 'page', __( 'Untitled page', 'breefer' ) );

		if ( $id > 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=breefer-editor&id=' . $id ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}

	/**
	 * Route Add New for pages/posts into the builder when enabled.
	 *
	 * @return void
	 */
	public function maybe_default_editor(): void {
		if ( ! Options::get( 'editor.default_editor', true ) ) {
			return;
		}

		$type = isset( $_GET['post_type'] ) && is_string( $_GET['post_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET['post_type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 'post';

		if ( ! in_array( $type, array( 'page', 'post' ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'page' === $type ? 'edit_pages' : 'edit_posts' ) ) {
			return;
		}

		$title = 'page' === $type ? __( 'Untitled page', 'breefer' ) : __( 'Untitled post', 'breefer' );
		$id    = self::create_document( $type, $title );

		if ( $id > 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=breefer-editor&id=' . $id ) );
			exit;
		}
	}

	/**
	 * Add admin bar links.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public function admin_bar( WP_Admin_Bar $bar ): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( is_singular() ) {
			$post = get_post();

			if ( $post instanceof WP_Post && in_array( $post->post_type, array( 'page', 'post' ), true ) && current_user_can( 'edit_post', $post->ID ) ) {
				$bar->add_node(
					array(
						'id'    => 'breefer-edit',
						'title' => __( 'Edit with Breefer', 'breefer' ),
						'href'  => admin_url( 'admin.php?page=breefer-editor&id=' . $post->ID ),
					)
				);
			}
		}

		if ( current_user_can( 'edit_pages' ) ) {
			$bar->add_node(
				array(
					'parent' => 'new-content',
					'id'     => 'breefer-new-page',
					'title'  => __( 'Breefer page', 'breefer' ),
					'href'   => admin_url( 'admin.php?page=breefer-new-page' ),
				)
			);
		}
	}

	/**
	 * Add an Edit with Breefer row action.
	 *
	 * @param array<string, string> $actions Actions.
	 * @param WP_Post               $post    Post.
	 * @return array<string, string>
	 */
	public function row_actions( array $actions, WP_Post $post ): array {
		if ( ! in_array( $post->post_type, array( 'page', 'post' ), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$actions['breefer'] = '<a href="' . esc_url( admin_url( 'admin.php?page=breefer-editor&id=' . $post->ID ) ) . '">' . esc_html__( 'Edit with Breefer', 'breefer' ) . '</a>';

		return $actions;
	}
}
