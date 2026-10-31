<?php
/**
 * Settings screen.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\Utils\Capabilities;
use Breefer\Utils\DesignSystem;
use Breefer\Utils\Options;
use Breefer\Utils\Palette;
use Breefer\Utils\View;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the Breefer settings screen.
 */
final class SettingsPage {

	/**
	 * Settings group name.
	 */
	private const GROUP = 'breefer_settings_group';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'add_option_' . Options::OPTION, array( $this, 'sync_capability' ), 10, 2 );
		add_action( 'update_option_' . Options::OPTION, array( $this, 'sync_capability' ), 10, 2 );
	}

	/**
	 * Register the settings option.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Options::OPTION,
			array(
				'type'              => 'array',
				'default'           => Options::defaults(),
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $input ): array {
		$current = Options::all();

		if ( ! is_array( $input ) ) {
			return $current;
		}

		$palettes = Palette::all();
		$palette  = isset( $input['appearance']['palette'] )
			? sanitize_key( (string) $input['appearance']['palette'] )
			: (string) $current['appearance']['palette'];

		if ( ! array_key_exists( $palette, $palettes ) ) {
			$palette = 'violet';
		}

		$autosave = isset( $input['editor']['autosave_interval'] )
			? absint( $input['editor']['autosave_interval'] )
			: (int) $current['editor']['autosave_interval'];

		$history = isset( $input['editor']['history_limit'] )
			? absint( $input['editor']['history_limit'] )
			: (int) $current['editor']['history_limit'];

		$width = isset( $input['editor']['canvas_width'] )
			? absint( $input['editor']['canvas_width'] )
			: (int) $current['editor']['canvas_width'];

		$performance = array();

		foreach ( array_keys( Options::defaults()['performance'] ) as $key ) {
			$performance[ $key ] = ! empty( $input['performance'][ $key ] );
		}

		$roles = array_keys( wp_roles()->roles );
		$role  = isset( $input['access']['role'] )
			? sanitize_key( (string) $input['access']['role'] )
			: (string) $current['access']['role'];

		if ( ! in_array( $role, $roles, true ) ) {
			$role = 'administrator';
		}

		return array(
			'appearance'  => array(
				'palette' => $palette,
			),
			'editor'      => array(
				'autosave_interval' => max( 5, min( 300, $autosave ) ),
				'history_limit'     => max( 10, min( 200, $history ) ),
				'canvas_width'      => max( 320, min( 2560, $width ) ),
				'default_editor'    => ! empty( $input['editor']['default_editor'] ),
			),
			'performance' => $performance,
			'access'      => array(
				'role' => $role,
			),
			'design'      => DesignSystem::sanitize( $input['design'] ?? $current['design'] ),
			'woocommerce' => array(
				'ajax_add_to_cart' => ! empty( $input['woocommerce']['ajax_add_to_cart'] ),
				'cart_fragments'   => ! empty( $input['woocommerce']['cart_fragments'] ),
				'sticky_cart'      => ! empty( $input['woocommerce']['sticky_cart'] ),
			),
		);
	}

	/**
	 * Move the Breefer capability to the configured role.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 * @return void
	 */
	public function sync_capability( mixed $old_value, mixed $value ): void {
		unset( $old_value );

		if ( ! is_array( $value ) || ! isset( $value['access']['role'] ) ) {
			return;
		}

		Capabilities::revoke_all();
		Capabilities::grant( 'administrator' );
		Capabilities::grant( (string) $value['access']['role'] );
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'breefer' ),
				esc_html__( 'Forbidden', 'breefer' ),
				array( 'response' => 403 )
			);
		}

		View::render(
			'admin/settings',
			array(
				'version'  => BREEFER_VERSION,
				'settings' => Options::all(),
				'palettes' => Palette::all(),
				'design'   => DesignSystem::get(),
				'tokens'   => DesignSystem::color_tokens(),
				'fonts'    => DesignSystem::fonts(),
				'roles'    => wp_roles()->get_names(),
				'group'    => self::GROUP,
			)
		);
	}
}
