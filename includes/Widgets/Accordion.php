<?php
/**
 * Accordion widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders native details/summary accordion items.
 */
final class Accordion extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'accordion';
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
		unset( $children_html, $node_id );

		$items = $this->items( $settings );

		if ( array() === $items ) {
			return '';
		}

		$open_first = $this->bool( $settings, 'openFirst' );
		$html       = '<div class="br-accordion">';

		foreach ( $items as $index => $item ) {
			$open = $open_first && 0 === $index;
			$html .= '<details class="br-accordion-item"' . ( $open ? ' open' : '' ) . '>';
			$html .= '<summary>' . esc_html( $item['title'] ) . '</summary>';
			$html .= '<div class="br-accordion-content">' . wp_kses_post( wpautop( $item['content'] ) ) . '</div>';
			$html .= '</details>';
		}

		return $html . '</div>';
	}

	/**
	 * Parse item lines.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<int, array{title: string, content: string}>
	 */
	private function items( array $settings ): array {
		$raw   = $this->str( $settings, 'items' );
		$lines = preg_split( '/\r\n|\r|\n/', $raw );

		if ( ! is_array( $lines ) ) {
			return array();
		}

		$items = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$parts   = explode( '|', $line, 2 );
			$title   = trim( (string) ( $parts[0] ?? '' ) );
			$content = trim( (string) ( $parts[1] ?? '' ) );

			if ( '' === $title && '' === $content ) {
				continue;
			}

			$items[] = array(
				'title'   => $title,
				'content' => $content,
			);
		}

		return $items;
	}
}
