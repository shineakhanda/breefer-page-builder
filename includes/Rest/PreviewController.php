<?php
/**
 * Widget preview REST controller.
 *
 * @package Breefer
 */

namespace Breefer\Rest;

use Breefer\Documents\Document;
use Breefer\Rendering\WidgetRegistry;
use Breefer\Utils\Context;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a single widget server-side so the editor canvas shows live data
 * (products, menus, post fields) without duplicating every renderer in React.
 */
final class PreviewController {

	/**
	 * Widget registry.
	 */
	private WidgetRegistry $registry;

	/**
	 * Constructor.
	 *
	 * @param WidgetRegistry $registry Registry.
	 */
	public function __construct( WidgetRegistry $registry ) {
		$this->registry = $registry;
	}

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
			'/render-widget',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'render' ),
				'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * Render a widget preview.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function render( WP_REST_Request $request ) {
		$type     = sanitize_key( (string) $request['type'] );
		$settings = $request['settings'];

		if ( '' === $type || ! $this->registry->has( $type ) || ! is_array( $settings ) ) {
			return new WP_Error( 'breefer_bad_widget', __( 'Unknown widget.', 'breefer' ), array( 'status' => 400 ) );
		}

		$clean = Document::sanitize(
			array(
				'version' => 1,
				'root'    => array(
					'id'       => 'root',
					'type'     => 'container',
					'settings' => array(),
					'children' => array(
						array(
							'id'       => 'preview',
							'type'     => $type,
							'settings' => $settings,
							'children' => array(),
						),
					),
				),
			)
		);

		$node = $clean['root']['children'][0] ?? null;

		if ( ! is_array( $node ) ) {
			return new WP_Error( 'breefer_bad_widget', __( 'Unknown widget.', 'breefer' ), array( 'status' => 400 ) );
		}

		$samples = Context::sample_ids();
		$doc     = absint( $request['postId'] );

		Context::begin_preview( $samples['post'], $samples['product'] );
		$previous = Context::set_document( $doc );

		try {
			$html = $this->registry->render(
				$type,
				isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array(),
				'',
				'preview'
			);
		} catch ( \Throwable $e ) {
			$html = '<div class="br-placeholder"><strong>' . esc_html__( 'Preview unavailable', 'breefer' ) . '</strong></div>';
		}

		Context::set_document( $previous );
		Context::end_preview();

		return rest_ensure_response( array( 'html' => $html ) );
	}
}
