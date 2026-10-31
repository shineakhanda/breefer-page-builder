<?php
/**
 * Builder meta box.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\Documents\Document;
use Breefer\PostTypes\Templates;
use Breefer\Theme\TemplateTypes;
use Breefer\Utils\View;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the Breefer toggle to pages, posts and templates.
 */
final class MetaBox {

	/**
	 * Nonce action.
	 */
	private const NONCE = 'breefer_meta';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register meta boxes.
	 *
	 * @return void
	 */
	public function add(): void {
		foreach ( array( 'page', 'post', Templates::TYPE ) as $type ) {
			add_meta_box(
				'breefer-builder',
				__( 'Breefer', 'breefer' ),
				array( $this, 'render' ),
				$type,
				'side',
				'high',
				array( '__block_editor_compatible_meta_box' => true )
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render( WP_Post $post ): void {
		View::render(
			'admin/meta-box',
			array(
				'post'          => $post,
				'enabled'       => Document::is_enabled( $post->ID ),
				'is_template'   => Templates::TYPE === $post->post_type,
				'editor_url'    => admin_url( 'admin.php?page=breefer-editor&id=' . $post->ID ),
				'nonce'         => wp_create_nonce( self::NONCE ),
				'woo_type'      => (string) get_post_meta( $post->ID, TemplateTypes::META, true ),
				'types'         => TemplateTypes::available(),
				'conditions'    => TemplateTypes::conditions(),
				'condition'     => (string) get_post_meta( $post->ID, TemplateTypes::CONDITION, true ) ?: 'all',
			)
		);
	}

	/**
	 * Save the enabled flag.
	 *
	 * @param int     $post_id Post id.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public function save( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['breefer_meta_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( (string) $_POST['breefer_meta_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( Templates::TYPE === $post->post_type ) {
			$woo_type = isset( $_POST['breefer_woo_type'] )
				? sanitize_key( wp_unslash( (string) $_POST['breefer_woo_type'] ) )
				: 'none';

			if ( ! TemplateTypes::exists( $woo_type ) ) {
				$woo_type = 'none';
			}

			if ( 'none' === $woo_type ) {
				delete_post_meta( $post_id, TemplateTypes::META );
			} else {
				update_post_meta( $post_id, TemplateTypes::META, $woo_type );
			}

			$condition = isset( $_POST['breefer_condition'] )
				? sanitize_key( wp_unslash( (string) $_POST['breefer_condition'] ) )
				: 'all';

			if ( ! isset( TemplateTypes::conditions()[ $condition ] ) || 'all' === $condition ) {
				delete_post_meta( $post_id, TemplateTypes::CONDITION );
			} else {
				update_post_meta( $post_id, TemplateTypes::CONDITION, $condition );
			}

			return;
		}

		$enabled = isset( $_POST['breefer_enabled'] ) && '1' === (string) $_POST['breefer_enabled'];

		Document::set_enabled( $post_id, $enabled );
	}
}
