<?php
/**
 * Capability helpers.
 *
 * @package Breefer
 */

namespace Breefer\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the plugin capability.
 */
final class Capabilities {

	/**
	 * Capability required to manage Breefer.
	 */
	public const MANAGE = 'manage_breefer';

	/**
	 * Grant the capability to a role.
	 *
	 * @param string $role_slug Role slug.
	 * @return void
	 */
	public static function grant( string $role_slug ): void {
		$role = get_role( sanitize_key( $role_slug ) );

		if ( null !== $role ) {
			$role->add_cap( self::MANAGE );
		}
	}

	/**
	 * Remove the capability from every role.
	 *
	 * @return void
	 */
	public static function revoke_all(): void {
		$roles = wp_roles()->roles;

		if ( ! is_array( $roles ) ) {
			return;
		}

		foreach ( array_keys( $roles ) as $slug ) {
			$role = get_role( (string) $slug );

			if ( null !== $role ) {
				$role->remove_cap( self::MANAGE );
			}
		}
	}

	/**
	 * Whether the current user can manage Breefer.
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( self::MANAGE ) || current_user_can( 'manage_options' );
	}
}
