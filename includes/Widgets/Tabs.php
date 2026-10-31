<?php
/**
 * Tabs widget.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders accessible tabs with a small vanilla JS island.
 */
final class Tabs extends AbstractWidget {

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return 'tabs';
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

		$items = $this->items( $settings );

		if ( array() === $items ) {
			return '';
		}

		$uid  = 'br-tabs-' . (string) preg_replace( '/[^a-z0-9]/', '', strtolower( $node_id ) );
		$html = '<div class="br-tabs" data-breefer-tabs>';

		$html .= '<div class="br-tabs-nav" role="tablist">';

		foreach ( $items as $index => $item ) {
			$selected = 0 === $index;
			$html    .= sprintf(
				'<button type="button" role="tab" id="%1$s-tab-%2$d" aria-controls="%1$s-panel-%2$d" aria-selected="%3$s" tabindex="%4$d" class="%5$s">%6$s</button>',
				esc_attr( $uid ),
				(int) $index,
				$selected ? 'true' : 'false',
				$selected ? 0 : -1,
				$selected ? 'is-active' : '',
				esc_html( $item['title'] )
			);
		}

		$html .= '</div><div class="br-tabs-panels">';

		foreach ( $items as $index => $item ) {
			$selected = 0 === $index;
			$html    .= sprintf(
				'<div class="br-tab-panel%1$s" id="%2$s-panel-%3$d" role="tabpanel" aria-labelledby="%2$s-tab-%3$d"%4$s>%5$s</div>',
				$selected ? ' is-active' : '',
				esc_attr( $uid ),
				(int) $index,
				$selected ? '' : ' hidden',
				wp_kses_post( wpautop( $item['content'] ) )
			);
		}

		return $html . '</div></div>';
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
