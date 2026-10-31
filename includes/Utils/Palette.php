<?php
/**
 * Editor and admin colour palettes.
 *
 * @package Breefer
 */

namespace Breefer\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Palette registry shared by the PHP admin screens and the React editor.
 *
 * Values intentionally mirror the Minimal Admin theme so Breefer looks like
 * a native part of the same product family.
 */
final class Palette {

	/**
	 * All palettes.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function all(): array {
		return array(
			'violet'      => array(
				'label'           => __( 'Violet', 'breefer' ),
				'mode'            => 'light',
				'accent'          => '#7c5cfc',
				'accent-2'        => '#a78bfa',
				'accent-soft'     => '#ece8ff',
				'accent-contrast' => '#ffffff',
				'sidebar'         => '#4b2d8a',
				'sidebar-2'       => '#352066',
				'sidebar-text'    => '#f7f4ff',
				'sidebar-muted'   => '#c3b6e8',
				'bg'              => '#efeafb',
				'surface'         => '#ffffff',
				'surface-2'       => '#f7f4fe',
				'text'            => '#1e1735',
				'muted'           => '#7d7395',
				'border'          => '#e8e1f7',
				'warn'            => '#e9b949',
				'danger'          => '#e35757',
				'info'            => '#2f80ed',
			),
			'violet-dark' => array(
				'label'           => __( 'Violet Dark', 'breefer' ),
				'mode'            => 'dark',
				'accent'          => '#b7a4ff',
				'accent-2'        => '#d8ccff',
				'accent-soft'     => '#2a2145',
				'accent-contrast' => '#17102b',
				'sidebar'         => '#1a1230',
				'sidebar-2'       => '#110b21',
				'sidebar-text'    => '#f3efff',
				'sidebar-muted'   => '#a89cc9',
				'bg'              => '#0d0917',
				'surface'         => '#151022',
				'surface-2'       => '#1d1630',
				'text'            => '#efeaff',
				'muted'           => '#a49ac0',
				'border'          => '#2b2342',
				'warn'            => '#f2cd6b',
				'danger'          => '#ff7a7a',
				'info'            => '#8bb8ff',
			),
			'sunset'      => array(
				'label'           => __( 'Sunset', 'breefer' ),
				'mode'            => 'light',
				'accent'          => '#ef7d34',
				'accent-2'        => '#f9a03f',
				'accent-soft'     => '#fdeadd',
				'accent-contrast' => '#ffffff',
				'sidebar'         => '#a8431d',
				'sidebar-2'       => '#7d2e10',
				'sidebar-text'    => '#fff7f2',
				'sidebar-muted'   => '#e8c4b1',
				'bg'              => '#fdf1e7',
				'surface'         => '#ffffff',
				'surface-2'       => '#fef7f1',
				'text'            => '#2d170c',
				'muted'           => '#94796b',
				'border'          => '#f3e2d5',
				'warn'            => '#e9b949',
				'danger'          => '#d94f4f',
				'info'            => '#2f80ed',
			),
			'rose'        => array(
				'label'           => __( 'Rose', 'breefer' ),
				'mode'            => 'light',
				'accent'          => '#e0568a',
				'accent-2'        => '#f472b6',
				'accent-soft'     => '#fce7f0',
				'accent-contrast' => '#ffffff',
				'sidebar'         => '#8c1f4e',
				'sidebar-2'       => '#681337',
				'sidebar-text'    => '#fff5f9',
				'sidebar-muted'   => '#e7b6c9',
				'bg'              => '#fbeaf1',
				'surface'         => '#ffffff',
				'surface-2'       => '#fdf4f8',
				'text'            => '#2c1220',
				'muted'           => '#987585',
				'border'          => '#f2dde7',
				'warn'            => '#e9b949',
				'danger'          => '#d94f4f',
				'info'            => '#2f80ed',
			),
			'ocean'       => array(
				'label'           => __( 'Ocean', 'breefer' ),
				'mode'            => 'light',
				'accent'          => '#2f80ed',
				'accent-2'        => '#60a5fa',
				'accent-soft'     => '#e2edfd',
				'accent-contrast' => '#ffffff',
				'sidebar'         => '#14508c',
				'sidebar-2'       => '#0b3866',
				'sidebar-text'    => '#f2f7fe',
				'sidebar-muted'   => '#a8c5e6',
				'bg'              => '#e8f0fa',
				'surface'         => '#ffffff',
				'surface-2'       => '#f4f8fe',
				'text'            => '#102538',
				'muted'           => '#728399',
				'border'          => '#e0e9f5',
				'warn'            => '#e9b949',
				'danger'          => '#e35757',
				'info'            => '#7c5cfc',
			),
			'midnight'    => array(
				'label'           => __( 'Midnight', 'breefer' ),
				'mode'            => 'dark',
				'accent'          => '#3ddc97',
				'accent-2'        => '#6ee7b7',
				'accent-soft'     => '#143a2c',
				'accent-contrast' => '#06251c',
				'sidebar'         => '#0a2f24',
				'sidebar-2'       => '#062019',
				'sidebar-text'    => '#eaf7f1',
				'sidebar-muted'   => '#93b8ab',
				'bg'              => '#0c1411',
				'surface'         => '#121c17',
				'surface-2'       => '#18251f',
				'text'            => '#e8f3ee',
				'muted'           => '#8aa096',
				'border'          => '#24352d',
				'warn'            => '#e9c14a',
				'danger'          => '#ef6464',
				'info'            => '#60a5fa',
			),
		);
	}

	/**
	 * Resolve a palette slug, falling back to violet.
	 *
	 * @param string $slug Palette slug.
	 * @return array<string, string>
	 */
	public static function resolve( string $slug ): array {
		$palettes = self::all();
		$slug     = sanitize_key( $slug );

		return $palettes[ $slug ] ?? $palettes['violet'];
	}

	/**
	 * Whether a palette uses a dark base.
	 *
	 * @param string $slug Palette slug.
	 * @return bool
	 */
	public static function is_dark( string $slug ): bool {
		$palette = self::resolve( $slug );

		return 'dark' === ( $palette['mode'] ?? 'light' );
	}

	/**
	 * Build the CSS custom property block for a palette.
	 *
	 * @param string $slug Palette slug.
	 * @return string
	 */
	public static function css( string $slug ): string {
		$palette = self::resolve( $slug );

		$variables = array(
			'accent'          => '--breefer-accent',
			'accent-2'        => '--breefer-accent-2',
			'accent-soft'     => '--breefer-accent-soft',
			'accent-contrast' => '--breefer-accent-contrast',
			'sidebar'         => '--breefer-sidebar',
			'sidebar-2'       => '--breefer-sidebar-2',
			'sidebar-text'    => '--breefer-sidebar-text',
			'sidebar-muted'   => '--breefer-sidebar-muted',
			'bg'              => '--breefer-bg',
			'surface'         => '--breefer-surface',
			'surface-2'       => '--breefer-surface-2',
			'text'            => '--breefer-text',
			'muted'           => '--breefer-muted',
			'border'          => '--breefer-border',
			'warn'            => '--breefer-warn',
			'danger'          => '--breefer-danger',
			'info'            => '--breefer-info',
		);

		$declarations = array();

		foreach ( $variables as $key => $property ) {
			if ( ! isset( $palette[ $key ] ) ) {
				continue;
			}

			$value = $palette[ $key ];

			if ( 1 !== preg_match( '/^#[a-fA-F0-9]{3,8}$/', $value ) ) {
				continue;
			}

			$declarations[] = $property . ':' . $value;
		}

		$declarations[] = 'color-scheme:' . ( self::is_dark( $slug ) ? 'dark' : 'light' );

		return ':root{' . implode( ';', $declarations ) . '}';
	}
}
