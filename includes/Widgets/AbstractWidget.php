<?php
/**
 * Widget base class.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

use Breefer\Utils\DesignSystem;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for server-side widget renderers.
 */
abstract class AbstractWidget {

	/**
	 * Widget type slug.
	 *
	 * @return string
	 */
	abstract public function type(): string;

	/**
	 * Render a widget.
	 *
	 * @param array<string, mixed> $settings       Widget settings.
	 * @param string               $children_html  Rendered children.
	 * @param string               $node_id        Node id.
	 * @return string
	 */
	abstract public function render( array $settings, string $children_html, string $node_id ): string;

	/**
	 * Read a string setting.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @param string               $default  Default.
	 * @return string
	 */
	protected function str( array $settings, string $key, string $default = '' ): string {
		$value = $settings[ $key ] ?? $default;

		return is_scalar( $value ) ? (string) $value : $default;
	}

	/**
	 * Read a numeric setting.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @param float                $default  Default.
	 * @return float
	 */
	protected function num( array $settings, string $key, float $default = 0 ): float {
		$value = $settings[ $key ] ?? $default;

		return is_numeric( $value ) ? (float) $value : $default;
	}

	/**
	 * Read a boolean setting.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @return bool
	 */
	protected function bool( array $settings, string $key ): bool {
		return ! empty( $settings[ $key ] );
	}

	/**
	 * Read a sanitized colour: a hex value or a "global:token" reference.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @param string               $default  Default.
	 * @return string
	 */
	protected function color( array $settings, string $key, string $default = '' ): string {
		$resolved = DesignSystem::resolve_color( $this->str( $settings, $key, $default ) );

		if ( null !== $resolved ) {
			return $resolved;
		}

		return DesignSystem::resolve_color( $default ) ?? '';
	}

	/**
	 * Read a value restricted to an allow-list.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @param array<int, string>   $allowed  Allowed values.
	 * @param string               $default  Default (must be allowed).
	 * @return string
	 */
	protected function choice( array $settings, string $key, array $allowed, string $default ): string {
		$value = $this->str( $settings, $key, $default );

		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Read an integer setting clamped to a range.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @param int                  $min      Minimum.
	 * @param int                  $max      Maximum.
	 * @param int                  $default  Default.
	 * @return int
	 */
	protected function int( array $settings, string $key, int $min, int $max, int $default ): int {
		return max( $min, min( $max, (int) $this->num( $settings, $key, (float) $default ) ) );
	}

	/**
	 * Split a multi-line setting into trimmed, non-empty lines.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @return array<int, string>
	 */
	protected function lines( array $settings, string $key ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $this->str( $settings, $key ) );

		if ( ! is_array( $lines ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'trim', $lines ), static fn( string $line ): bool => '' !== $line ) );
	}

	/**
	 * Split a pipe separated line into trimmed parts, padded to a minimum count.
	 *
	 * @param string $line  Line.
	 * @param int    $count Minimum number of parts to return.
	 * @return array<int, string>
	 */
	protected function parts( string $line, int $count = 2 ): array {
		return array_pad( array_map( 'trim', explode( '|', $line ) ), $count, '' );
	}

	/**
	 * Build an inline style attribute of CSS custom properties.
	 *
	 * Empty values are skipped; names must be valid custom property names.
	 *
	 * @param array<string, string> $vars Name => value, e.g. '--br-accent' => '#fff'.
	 * @return string
	 */
	protected function vars( array $vars ): string {
		$parts = array();

		foreach ( $vars as $name => $value ) {
			if ( '' === $value || 1 !== preg_match( '/^--[a-z0-9-]+$/', $name ) ) {
				continue;
			}

			$parts[] = $name . ':' . $value;
		}

		return array() === $parts ? '' : ' style="' . esc_attr( implode( ';', $parts ) ) . '"';
	}

	/**
	 * Pixel value for a CSS variable, or an empty string when unset.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @param int                  $min      Minimum.
	 * @param int                  $max      Maximum.
	 * @return string
	 */
	protected function px( array $settings, string $key, int $min, int $max ): string {
		if ( ! isset( $settings[ $key ] ) || ! is_numeric( $settings[ $key ] ) ) {
			return '';
		}

		return max( $min, min( $max, (int) $settings[ $key ] ) ) . 'px';
	}

	/**
	 * Placeholder shown in the editor for widgets that need a live request.
	 *
	 * @param string $title Widget title.
	 * @param string $hint  Short explanation.
	 * @return string
	 */
	protected function placeholder( string $title, string $hint = '' ): string {
		return '<div class="br-placeholder"><strong>' . esc_html( $title ) . '</strong>'
			. ( '' !== $hint ? '<span>' . esc_html( $hint ) . '</span>' : '' ) . '</div>';
	}

	/**
	 * Render a link-style button used by several widgets.
	 *
	 * @param string $text  Label.
	 * @param string $url   URL.
	 * @param bool   $blank Open in a new tab.
	 * @param string $class Extra classes.
	 * @return string
	 */
	protected function link_button( string $text, string $url, bool $blank = false, string $class = '' ): string {
		if ( '' === trim( $text ) ) {
			return '';
		}

		return sprintf(
			'<a class="br-w-btn %1$s" href="%2$s"%3$s>%4$s</a>',
			esc_attr( $class ),
			esc_url( '' !== $url ? $url : '#' ),
			$blank ? ' target="_blank" rel="noopener noreferrer"' : '',
			esc_html( $text )
		);
	}
}
