<?php
/**
 * Global design system (colours, typography, shape).
 *
 * @package Breefer
 */

namespace Breefer\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide design tokens exposed as CSS custom properties.
 *
 * Widgets can reference a token with the value "global:primary", which resolves
 * to var(--br-color-primary). Changing a token restyles every page at once.
 */
final class DesignSystem {

	/**
	 * Font stacks. No remote fonts are ever requested.
	 *
	 * @return array<string, array{label: string, stack: string}>
	 */
	public static function fonts(): array {
		return array(
			'inherit'  => array(
				'label' => __( 'Theme font', 'breefer' ),
				'stack' => 'inherit',
			),
			'system'   => array(
				'label' => __( 'System UI', 'breefer' ),
				'stack' => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif",
			),
			'humanist' => array(
				'label' => __( 'Humanist', 'breefer' ),
				'stack' => "Seravek,'Gill Sans Nova',Ubuntu,Calibri,'DejaVu Sans',source-sans-pro,sans-serif",
			),
			'geometric' => array(
				'label' => __( 'Geometric', 'breefer' ),
				'stack' => "Avenir,Montserrat,Corbel,'URW Gothic',source-sans-pro,sans-serif",
			),
			'serif'    => array(
				'label' => __( 'Serif', 'breefer' ),
				'stack' => "Charter,'Bitstream Charter','Sitka Text',Cambria,Georgia,serif",
			),
			'mono'     => array(
				'label' => __( 'Monospace', 'breefer' ),
				'stack' => "ui-monospace,'Cascadia Code','Source Code Pro',Menlo,Consolas,monospace",
			),
		);
	}

	/**
	 * Colour tokens with labels and defaults.
	 *
	 * @return array<string, array{label: string, default: string}>
	 */
	public static function color_tokens(): array {
		return array(
			'primary'    => array(
				'label'   => __( 'Primary', 'breefer' ),
				'default' => '#7c5cfc',
			),
			'secondary'  => array(
				'label'   => __( 'Secondary', 'breefer' ),
				'default' => '#1e1735',
			),
			'accent'     => array(
				'label'   => __( 'Accent', 'breefer' ),
				'default' => '#f5b301',
			),
			'text'       => array(
				'label'   => __( 'Text', 'breefer' ),
				'default' => '#1e1735',
			),
			'muted'      => array(
				'label'   => __( 'Muted text', 'breefer' ),
				'default' => '#6b6483',
			),
			'surface'    => array(
				'label'   => __( 'Surface', 'breefer' ),
				'default' => '#f7f4fe',
			),
			'border'     => array(
				'label'   => __( 'Border', 'breefer' ),
				'default' => '#e8e1f7',
			),
			'background' => array(
				'label'   => __( 'Background', 'breefer' ),
				'default' => '#ffffff',
			),
		);
	}

	/**
	 * Default design settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$colors = array();

		foreach ( self::color_tokens() as $slug => $token ) {
			$colors[ $slug ] = $token['default'];
		}

		return array(
			'colors'     => $colors,
			'typography' => array(
				'body_font'      => 'system',
				'heading_font'   => 'system',
				'base_size'      => 16,
				'line_height'    => 1.6,
				'heading_weight' => 700,
				'scale'          => 1.25,
			),
			'shape'      => array(
				'radius'    => 12,
				'container' => 1160,
				'space'     => 8,
			),
		);
	}

	/**
	 * Stored design settings merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored   = Options::get( 'design', array() );
		$defaults = self::defaults();

		if ( ! is_array( $stored ) ) {
			return $defaults;
		}

		foreach ( $defaults as $group => $values ) {
			if ( isset( $stored[ $group ] ) && is_array( $stored[ $group ] ) ) {
				$defaults[ $group ] = array_merge( $values, $stored[ $group ] );
			}
		}

		return $defaults;
	}

	/**
	 * Sanitize submitted design settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( mixed $input ): array {
		$clean = self::defaults();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		foreach ( array_keys( self::color_tokens() ) as $slug ) {
			$value = $input['colors'][ $slug ] ?? null;

			if ( is_string( $value ) ) {
				$hex = sanitize_hex_color( $value );

				if ( is_string( $hex ) ) {
					$clean['colors'][ $slug ] = $hex;
				}
			}
		}

		$fonts = array_keys( self::fonts() );
		$typo  = isset( $input['typography'] ) && is_array( $input['typography'] ) ? $input['typography'] : array();

		foreach ( array( 'body_font', 'heading_font' ) as $key ) {
			$value = isset( $typo[ $key ] ) ? sanitize_key( (string) $typo[ $key ] ) : '';

			if ( in_array( $value, $fonts, true ) ) {
				$clean['typography'][ $key ] = $value;
			}
		}

		$clean['typography']['base_size']      = self::clamp( $typo['base_size'] ?? null, 12, 24, 16 );
		$clean['typography']['line_height']    = self::clamp( $typo['line_height'] ?? null, 1.1, 2.4, 1.6 );
		$clean['typography']['heading_weight'] = (int) ( round( self::clamp( $typo['heading_weight'] ?? null, 300, 900, 700 ) / 100 ) * 100 );
		$clean['typography']['scale']          = self::clamp( $typo['scale'] ?? null, 1.05, 1.6, 1.25 );

		$shape = isset( $input['shape'] ) && is_array( $input['shape'] ) ? $input['shape'] : array();

		$clean['shape']['radius']    = (int) self::clamp( $shape['radius'] ?? null, 0, 48, 12 );
		$clean['shape']['container'] = (int) self::clamp( $shape['container'] ?? null, 640, 2000, 1160 );
		$clean['shape']['space']     = (int) self::clamp( $shape['space'] ?? null, 2, 16, 8 );

		return $clean;
	}

	/**
	 * Resolve a stored colour value to CSS.
	 *
	 * Accepts a hex colour or a "global:slug" token reference.
	 *
	 * @param mixed $value Stored value.
	 * @return string|null
	 */
	public static function resolve_color( mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( 1 === preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
			return $value;
		}

		if ( 1 === preg_match( '/^global:([a-z]+)$/', $value, $match ) && isset( self::color_tokens()[ $match[1] ] ) ) {
			return 'var(--br-color-' . $match[1] . ')';
		}

		return null;
	}

	/**
	 * Whether a value is a valid colour reference (hex or token).
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	public static function is_color_reference( string $value ): bool {
		return null !== self::resolve_color( $value );
	}

	/**
	 * Token list for the editor.
	 *
	 * @return array<int, array{slug: string, label: string, value: string}>
	 */
	public static function tokens_for_editor(): array {
		$design = self::get();
		$out    = array();

		foreach ( self::color_tokens() as $slug => $token ) {
			$out[] = array(
				'slug'  => $slug,
				'label' => $token['label'],
				'value' => (string) ( $design['colors'][ $slug ] ?? $token['default'] ),
			);
		}

		return $out;
	}

	/**
	 * Build the :root CSS custom properties.
	 *
	 * @return string
	 */
	public static function css(): string {
		$design = self::get();
		$fonts  = self::fonts();
		$typo   = $design['typography'];
		$shape  = $design['shape'];
		$vars   = array();

		foreach ( self::color_tokens() as $slug => $token ) {
			$value = sanitize_hex_color( (string) ( $design['colors'][ $slug ] ?? '' ) );

			$vars[ '--br-color-' . $slug ] = is_string( $value ) ? $value : $token['default'];
		}

		$body_font    = $fonts[ $typo['body_font'] ]['stack'] ?? $fonts['system']['stack'];
		$heading_font = $fonts[ $typo['heading_font'] ]['stack'] ?? $fonts['system']['stack'];
		$base         = (float) $typo['base_size'];
		$scale        = (float) $typo['scale'];

		$vars['--br-font-body']      = $body_font;
		$vars['--br-font-heading']   = $heading_font;
		$vars['--br-size-base']      = self::num( $base ) . 'px';
		$vars['--br-line-height']    = self::num( (float) $typo['line_height'] );
		$vars['--br-heading-weight'] = (string) (int) $typo['heading_weight'];
		$vars['--br-radius']         = (int) $shape['radius'] . 'px';
		$vars['--br-container']      = (int) $shape['container'] . 'px';
		$vars['--br-space']          = (int) $shape['space'] . 'px';

		// Modular type scale: h6 is one step above the base size.
		for ( $level = 6; $level >= 1; $level-- ) {
			$step                          = 7 - $level;
			$vars[ '--br-size-h' . $level ] = self::num( round( $base * ( $scale ** $step ), 2 ) ) . 'px';
		}

		$parts = array();

		foreach ( $vars as $name => $value ) {
			$parts[] = $name . ':' . $value;
		}

		return ':root{' . implode( ';', $parts ) . '}';
	}

	/**
	 * Clamp a numeric input.
	 *
	 * @param mixed $value   Value.
	 * @param float $min     Minimum.
	 * @param float $max     Maximum.
	 * @param float $default Fallback.
	 * @return float
	 */
	private static function clamp( mixed $value, float $min, float $max, float $default ): float {
		if ( ! is_numeric( $value ) ) {
			return $default;
		}

		return max( $min, min( $max, (float) $value ) );
	}

	/**
	 * Format a number without trailing zeros.
	 *
	 * @param float $number Number.
	 * @return string
	 */
	private static function num( float $number ): string {
		return rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' );
	}
}
