<?php
/**
 * Activation routine.
 *
 * @package Breefer
 */

namespace Breefer;

use Breefer\Admin\WelcomeRedirect;
use Breefer\Utils\Capabilities;
use Breefer\Utils\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Installs Breefer on activation.
 */
final class Activation {

	/**
	 * Run on plugin activation.
	 *
	 * @param bool $network_wide Whether the plugin is activated network-wide.
	 * @return void
	 */
	public static function activate( bool $network_wide = false ): void {
		if ( version_compare( PHP_VERSION, BREEFER_MIN_PHP, '<' ) ) {
			deactivate_plugins( BREEFER_BASENAME );

			wp_die(
				esc_html(
					sprintf(
						/* translators: %s: required PHP version. */
						__( 'Breefer requires PHP %s or newer. The plugin was deactivated.', 'breefer' ),
						BREEFER_MIN_PHP
					)
				),
				esc_html__( 'Breefer requirements not met', 'breefer' ),
				array( 'back_link' => true )
			);
		}

		if ( is_multisite() && $network_wide ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::install_site();
				restore_current_blog();
			}

			return;
		}

		self::install_site();
	}

	/**
	 * Install a single site.
	 *
	 * @return void
	 */
	private static function install_site(): void {
		if ( false === get_option( Options::OPTION, false ) ) {
			add_option( Options::OPTION, Options::defaults() );
		}

		update_option( 'breefer_version', BREEFER_VERSION );
		update_option( 'breefer_db_version', BREEFER_DB_VERSION );

		Capabilities::grant( 'administrator' );
		Capabilities::grant( (string) Options::get( 'access.role', 'administrator' ) );

		self::prepare_uploads();

		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			update_user_meta( $user_id, WelcomeRedirect::META_KEY, '1' );
		}

		flush_rewrite_rules( false );
	}

	/**
	 * Create the plugin uploads directory and its index guard.
	 *
	 * @return void
	 */
	private static function prepare_uploads(): void {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return;
		}

		$path = trailingslashit( (string) $uploads['basedir'] ) . 'breefer';

		if ( ! wp_mkdir_p( $path ) ) {
			return;
		}

		$index = trailingslashit( $path ) . 'index.php';

		if ( ! file_exists( $index ) && is_writable( $path ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}
}
