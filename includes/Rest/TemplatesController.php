<?php
/**
 * Document REST controller.
 *
 * @package Breefer
 */

namespace Breefer\Rest;

use Breefer\Documents\Document;
use Breefer\Frontend\Frontend;
use Breefer\Theme\TemplateTypes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes builder documents under breefer/v1.
 */
final class TemplatesController {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function routes(): void {
		register_rest_route(
			'breefer/v1',
			'/documents',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => array( $this, 'can_edit_any' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_edit_any' ),
				),
			)
		);

		register_rest_route(
			'breefer/v1',
			'/documents/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'can_edit' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_edit' ),
				),
			)
		);
	}

	/**
	 * Any user who can edit posts may list templates.
	 *
	 * @return bool
	 */
	public function can_edit_any(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Per-document permission.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function can_edit( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', absint( $request['id'] ) );
	}

	/**
	 * List templates.
	 *
	 * @return WP_REST_Response
	 */
	public function index(): WP_REST_Response {
		$posts = get_posts(
			array(
				'post_type'        => 'breefer_template',
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'numberposts'      => 100,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => true,
			)
		);

		$items = array();

		foreach ( $posts as $post ) {
			$items[] = array(
				'id'       => $post->ID,
				'title'    => get_the_title( $post ),
				'status'   => $post->post_status,
				'modified' => $post->post_modified_gmt,
			);
		}

		return rest_ensure_response( array( 'items' => $items ) );
	}

	/**
	 * Create a template.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$title = $request['title'] ?? __( 'Untitled template', 'breefer' );
		$title = is_string( $title ) ? sanitize_text_field( $title ) : __( 'Untitled template', 'breefer' );

		$id = wp_insert_post(
			array(
				'post_type'   => 'breefer_template',
				'post_status' => 'draft',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		Document::save( (int) $id, Document::seed_document() );

		return rest_ensure_response( $this->payload( (int) $id ) );
	}

	/**
	 * Read a document.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id   = absint( $request['id'] );
		$post = get_post( $id );

		if ( null === $post ) {
			return new WP_Error( 'breefer_not_found', __( 'Document not found.', 'breefer' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->payload( $id ) );
	}

	/**
	 * Save a document.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id   = absint( $request['id'] );
		$post = get_post( $id );

		if ( null === $post ) {
			return new WP_Error( 'breefer_not_found', __( 'Document not found.', 'breefer' ), array( 'status' => 404 ) );
		}

		$data = $request['data'];

		if ( null === $data ) {
			return new WP_Error( 'breefer_missing_data', __( 'No document data was sent.', 'breefer' ), array( 'status' => 400 ) );
		}

		$document = Document::sanitize( $data );

		if ( ! Document::save( $id, $document ) ) {
			return new WP_Error( 'breefer_save_failed', __( 'The document could not be saved.', 'breefer' ), array( 'status' => 500 ) );
		}

		// Saving from the builder turns the builder on for regular content.
		if ( in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
			Document::set_enabled( $id, true );
		}

		if ( 'breefer_template' === $post->post_type && isset( $request['wooType'] ) && is_string( $request['wooType'] ) ) {
			$woo_type = sanitize_key( $request['wooType'] );

			if ( ! TemplateTypes::exists( $woo_type ) ) {
				$woo_type = 'none';
			}

			if ( 'none' === $woo_type ) {
				delete_post_meta( $id, TemplateTypes::META );
			} else {
				update_post_meta( $id, TemplateTypes::META, $woo_type );
			}
		}

		if ( 'breefer_template' === $post->post_type && isset( $request['condition'] ) && is_string( $request['condition'] ) ) {
			$condition = sanitize_key( $request['condition'] );

			if ( ! isset( TemplateTypes::conditions()[ $condition ] ) || 'all' === $condition ) {
				delete_post_meta( $id, TemplateTypes::CONDITION );
			} else {
				update_post_meta( $id, TemplateTypes::CONDITION, $condition );
			}
		}

		if ( in_array( $post->post_type, array( 'page', 'post' ), true ) && isset( $request['layout'] ) && is_string( $request['layout'] ) ) {
			$layout = sanitize_key( $request['layout'] );

			if ( ! in_array( $layout, array( 'theme', 'full', 'canvas' ), true ) ) {
				$layout = 'theme';
			}

			if ( 'theme' === $layout ) {
				delete_post_meta( $id, Frontend::LAYOUT );
			} else {
				update_post_meta( $id, Frontend::LAYOUT, $layout );
			}
		}

		if ( in_array( $post->post_type, array( 'page', 'post' ), true ) && isset( $request['status'] ) && is_string( $request['status'] ) ) {
			$status = sanitize_key( $request['status'] );

			if ( in_array( $status, array( 'draft', 'publish', 'private' ), true ) && $post->post_status !== $status ) {
				$capability = 'page' === $post->post_type ? 'publish_pages' : 'publish_posts';

				if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( $capability ) ) {
					return new WP_Error(
						'breefer_publish_forbidden',
						__( 'You do not have permission to publish this content.', 'breefer' ),
						array( 'status' => 403 )
					);
				}

				wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => $status,
					)
				);
			}
		}

		$title = $request['title'];

		if ( is_string( $title ) ) {
			$title = sanitize_text_field( $title );

			if ( '' !== $title && $title !== $post->post_title ) {
				wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $title,
					)
				);
			}
		}

		return rest_ensure_response( $this->payload( $id ) );
	}

	/**
	 * Document payload.
	 *
	 * @param int $id Post id.
	 * @return array<string, mixed>
	 */
	private function payload( int $id ): array {
		return array(
			'id'       => $id,
			'title'    => get_the_title( $id ),
			'status'   => get_post_status( $id ),
			'modified' => get_post_modified_time( 'c', true, $id ),
			'data'     => Document::get( $id ),
			'wooType'  => (string) get_post_meta( $id, TemplateTypes::META, true ),
			'condition' => (string) get_post_meta( $id, TemplateTypes::CONDITION, true ) ?: 'all',
			'layout'   => (string) get_post_meta( $id, Frontend::LAYOUT, true ) ?: 'theme',
		);
	}
}
