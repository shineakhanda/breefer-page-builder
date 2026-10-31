<?php
/**
 * Form submission REST controller.
 *
 * @package Breefer
 */

namespace Breefer\Rest;

use Breefer\Documents\Document;
use Breefer\Widgets\FormWidget;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Receives submissions from the Form widget and emails them.
 *
 * The recipient, subject and field list are read from the saved document, never
 * from the request, so a visitor cannot redirect mail or add fields.
 */
final class FormsController {

	/**
	 * Submissions allowed per visitor in the rate-limit window.
	 */
	private const LIMIT = 5;

	/**
	 * Rate-limit window in seconds.
	 */
	private const WINDOW = 600;

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
			'/forms/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle a submission.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function submit( WP_REST_Request $request ) {
		// Honeypot: real visitors never fill this in. Report success to bots.
		if ( '' !== trim( (string) $request['website'] ) ) {
			return rest_ensure_response( array( 'message' => __( 'Thank you!', 'breefer' ) ) );
		}

		if ( $this->rate_limited() ) {
			return new WP_Error( 'breefer_form_rate', __( 'Too many messages. Please try again later.', 'breefer' ), array( 'status' => 429 ) );
		}

		$doc  = absint( $request['doc'] );
		$node = Document::find_node( Document::get( $doc ), sanitize_key( (string) $request['node'] ) );

		if ( $doc <= 0 || null === $node || 'form' !== ( $node['type'] ?? '' ) ) {
			return new WP_Error( 'breefer_form_missing', __( 'This form is not available.', 'breefer' ), array( 'status' => 404 ) );
		}

		$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
		$fields   = FormWidget::parse_fields( $settings );
		$lines    = array();
		$reply_to = '';

		foreach ( $fields as $index => $field ) {
			$raw   = $request[ 'field_' . $index ];
			$value = is_scalar( $raw ) ? (string) $raw : '';
			$value = 'textarea' === $field['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );

			if ( '' === $value ) {
				if ( $field['required'] ) {
					/* translators: %s: field label. */
					return new WP_Error( 'breefer_form_required', sprintf( __( '"%s" is required.', 'breefer' ), $field['label'] ), array( 'status' => 400 ) );
				}

				continue;
			}

			if ( 'email' === $field['type'] ) {
				$email = sanitize_email( $value );

				if ( ! is_email( $email ) ) {
					/* translators: %s: field label. */
					return new WP_Error( 'breefer_form_email', sprintf( __( '"%s" must be a valid email address.', 'breefer' ), $field['label'] ), array( 'status' => 400 ) );
				}

				$value    = $email;
				$reply_to = '' === $reply_to ? $email : $reply_to;
			}

			if ( 'url' === $field['type'] ) {
				$value = esc_url_raw( $value );
			}

			$lines[] = $field['label'] . ': ' . $value;
		}

		if ( array() === $lines ) {
			return new WP_Error( 'breefer_form_empty', __( 'Please fill in the form.', 'breefer' ), array( 'status' => 400 ) );
		}

		$to = isset( $settings['to'] ) && is_string( $settings['to'] ) ? sanitize_email( $settings['to'] ) : '';

		if ( ! is_email( $to ) ) {
			$to = (string) get_option( 'admin_email' );
		}

		$subject = isset( $settings['subject'] ) && is_string( $settings['subject'] ) && '' !== trim( $settings['subject'] )
			? sanitize_text_field( $settings['subject'] )
			/* translators: %s: site name. */
			: sprintf( __( 'New message from %s', 'breefer' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		if ( '' !== $reply_to ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$body = implode( "\n\n", $lines ) . "\n\n--\n" . get_permalink( $doc );

		/**
		 * Filter the form email before it is sent.
		 *
		 * @param array $mail     Keys: to, subject, body, headers.
		 * @param array $settings Form widget settings.
		 */
		$mail = (array) apply_filters(
			'breefer/forms/mail',
			array(
				'to'      => $to,
				'subject' => $subject,
				'body'    => $body,
				'headers' => $headers,
			),
			$settings
		);

		$sent = wp_mail( (string) $mail['to'], (string) $mail['subject'], (string) $mail['body'], (array) $mail['headers'] );

		if ( ! $sent ) {
			return new WP_Error( 'breefer_form_failed', __( 'The message could not be sent. Please try again later.', 'breefer' ), array( 'status' => 500 ) );
		}

		do_action( 'breefer/forms/submitted', $doc, $node, $lines );

		$message = isset( $settings['successMessage'] ) && is_string( $settings['successMessage'] ) && '' !== trim( $settings['successMessage'] )
			? sanitize_text_field( $settings['successMessage'] )
			: __( 'Thank you! Your message has been sent.', 'breefer' );

		return rest_ensure_response( array( 'message' => $message ) );
	}

	/**
	 * Count this visitor's submission and report whether the limit is exceeded.
	 *
	 * @return bool
	 */
	private function rate_limited(): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'breefer_form_' . md5( $ip . wp_salt() );

		$count = (int) get_transient( $key );

		if ( $count >= self::LIMIT ) {
			return true;
		}

		set_transient( $key, $count + 1, self::WINDOW );

		return false;
	}
}
