<?php
/**
 * Template post type.
 *
 * @package Breefer
 */

namespace Breefer\PostTypes;

use Breefer\Documents\Document;
use Breefer\Theme\TemplateTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the reusable template post type and editor redirects.
 */
final class Templates {

	/**
	 * Post type slug.
	 */
	public const TYPE = 'breefer_template';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ), 5 );
		add_action( 'load-post-new.php', array( $this, 'redirect_new' ) );
		add_filter( 'get_edit_post_link', array( $this, 'edit_link' ), 10, 3 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'use_block_editor' ), 10, 2 );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Templates', 'breefer' ),
					'singular_name'      => __( 'Template', 'breefer' ),
					'add_new'            => __( 'Add template', 'breefer' ),
					'add_new_item'       => __( 'Add template', 'breefer' ),
					'edit_item'          => __( 'Edit with Breefer', 'breefer' ),
					'new_item'           => __( 'New template', 'breefer' ),
					'view_item'          => __( 'View template', 'breefer' ),
					'search_items'       => __( 'Search templates', 'breefer' ),
					'not_found'          => __( 'No templates found.', 'breefer' ),
					'not_found_in_trash' => __( 'No templates in the trash.', 'breefer' ),
					'all_items'          => __( 'Templates', 'breefer' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-layout',
				'rewrite'         => false,
				'delete_with_user' => false,
			)
		);
	}

	/**
	 * Redirect the default "Add New" screen into the builder.
	 *
	 * @return void
	 */
	public function redirect_new(): void {
		if ( ! isset( $_GET['post_type'] ) || ! is_string( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$type = sanitize_key( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( self::TYPE !== $type || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => self::TYPE,
				'post_status' => 'draft',
				'post_title'  => __( 'Untitled template', 'breefer' ),
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return;
		}

		Document::save( (int) $id, Document::seed_document() );

		$template_type = isset( $_GET['breefer_type'] ) && is_string( $_GET['breefer_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET['breefer_type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		if ( TemplateTypes::exists( $template_type ) ) {
			update_post_meta( (int) $id, TemplateTypes::META, $template_type );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=breefer-editor&id=' . (int) $id ) );
		exit;
	}

	/**
	 * Point edit links at the builder.
	 *
	 * @param string|null $link    Edit link.
	 * @param int         $post_id Post id.
	 * @param string      $context Context.
	 * @return string
	 */
	public function edit_link( ?string $link, int $post_id, string $context ): string {
		unset( $context );

		if ( self::TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return $link ?? '';
		}

		return admin_url( 'admin.php?page=breefer-editor&id=' . $post_id );
	}

	/**
	 * Disable the block editor for templates.
	 *
	 * @param bool   $use       Whether to use the block editor.
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public function use_block_editor( bool $use, string $post_type ): bool {
		return self::TYPE === $post_type ? false : $use;
	}

	/**
	 * Template list columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$updated = array();

		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;

			if ( 'title' === $key ) {
				$updated['breefer_shortcode'] = __( 'Shortcode', 'breefer' );
			}
		}

		return $updated;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post id.
	 * @return void
	 */
	public function column( string $column, int $post_id ): void {
		if ( 'breefer_type' === $column ) {
			$type  = (string) get_post_meta( $post_id, TemplateTypes::META, true );
			$types = TemplateTypes::all();

			echo esc_html( isset( $types[ $type ] ) ? $types[ $type ]['label'] : __( 'Reusable block', 'breefer' ) );

			return;
		}

		if ( 'breefer_shortcode' !== $column ) {
			return;
		}

		echo '<code>[breefer id="' . esc_html( (string) $post_id ) . '"]</code>';
	}
}
