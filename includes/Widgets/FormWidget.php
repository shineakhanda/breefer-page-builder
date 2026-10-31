<?php
/**
 * Contact form widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\Context;

defined( 'ABSPATH' ) || exit;

/**
 * Contact form. Fields are described one per line as "Label | type | *".
 *
 * Submissions go to the REST endpoint in Rest\FormsController, which re-reads
 * the saved field definitions so the client can never change the recipient.
 */
final class FormWidget extends AbstractWidget {

	/**
	 * Allowed field types.
	 */
	public const TYPES = array( 'text', 'email', 'tel', 'number', 'url', 'textarea' );

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'form';
	}

	/**
	 * Parse the field definitions of a form node.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<int, array{label: string, type: string, required: bool}>
	 */
	public static function parse_fields( array $settings ): array {
		$raw    = isset( $settings['fields'] ) && is_string( $settings['fields'] ) ? $settings['fields'] : '';
		$lines  = preg_split( '/\r\n|\r|\n/', $raw );
		$fields = array();

		foreach ( is_array( $lines ) ? $lines : array() as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$parts = array_pad( array_map( 'trim', explode( '|', $line ) ), 3, '' );
			$type  = in_array( $parts[1], self::TYPES, true ) ? $parts[1] : 'text';

			$fields[] = array(
				'label'    => $parts[0],
				'type'     => $type,
				'required' => '*' === $parts[2] || 'required' === strtolower( $parts[2] ),
			);

			if ( count( $fields ) >= 20 ) {
				break;
			}
		}

		return $fields;
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

		$fields = self::parse_fields( $settings );

		if ( array() === $fields ) {
			return $this->placeholder( __( 'Form', 'breefer' ), __( 'Add one field per line, e.g. Email | email | *', 'breefer' ) );
		}

		$uid  = 'br-form-' . preg_replace( '/[^a-z0-9]/', '', strtolower( $node_id ) );
		$vars = $this->vars(
			array(
				'--br-form-accent' => $this->color( $settings, 'buttonColor' ),
				'--br-form-radius' => $this->px( $settings, 'radius', 0, 40 ),
			)
		);

		$html  = '<form class="br-form" method="post" data-breefer-form novalidate' . $vars . '>';
		$html .= '<input type="hidden" name="doc" value="' . esc_attr( (string) Context::document() ) . '">';
		$html .= '<input type="hidden" name="node" value="' . esc_attr( $node_id ) . '">';
		$html .= '<div class="br-form-hp" aria-hidden="true"><label>' . esc_html__( 'Leave this empty', 'breefer' ) . '<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';

		foreach ( $fields as $index => $field ) {
			$id       = $uid . '-' . $index;
			$required = $field['required'] ? ' required aria-required="true"' : '';
			$label    = esc_html( $field['label'] ) . ( $field['required'] ? ' <span class="br-form-req" aria-hidden="true">*</span>' : '' );

			$html .= '<div class="br-form-field is-' . esc_attr( $field['type'] ) . '">';
			$html .= '<label for="' . esc_attr( $id ) . '">' . $label . '</label>';

			if ( 'textarea' === $field['type'] ) {
				$html .= '<textarea id="' . esc_attr( $id ) . '" name="field_' . (int) $index . '" rows="5"' . $required . '></textarea>';
			} else {
				$html .= '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $field['type'] ) . '" name="field_' . (int) $index . '"' . $required . '>';
			}

			$html .= '</div>';
		}

		$html .= '<button type="submit" class="br-w-btn br-form-submit">' . esc_html( $this->str( $settings, 'submitText', __( 'Send message', 'breefer' ) ) ) . '</button>';
		$html .= '<p class="br-form-status" role="status" aria-live="polite"></p>';
		$html .= '<noscript><p class="br-form-status is-error">' . esc_html__( 'Please enable JavaScript to send this form.', 'breefer' ) . '</p></noscript>';

		return $html . '</form>';
	}
}
