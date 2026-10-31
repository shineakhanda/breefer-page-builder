<?php
/**
 * Deactivation routine.
 *
 * @package Breefer
 */

namespace Breefer;

use Breefer\Admin\WelcomeRedirect;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans up transient runtime state without deleting user data.
 */
final class Deactivation {

	/**
	 * Run on plugin deactivation.
	 *
	 * @param bool $network_wide Whether the plugin is deactivated network-wide.
	 * @return void
	 */
	public static function deactivate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::cleanup_site();
				restore_current_blog();
			}

			return;
		}

		self::cleanup_site();
	}

	/**
	 * Clean up a single site.
	 *
	 * @return void
	 */
	private static function cleanup_site(): void {
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			delete_user_meta( $user_id, WelcomeRedirect::META_KEY );
		}

		flush_rewrite_rules( false );
	}
}
