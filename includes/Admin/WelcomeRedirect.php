<?php
/**
 * One-time welcome redirect.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\Utils\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Sends the activating administrator to the Breefer dashboard once.
 */
final class WelcomeRedirect {

	/**
	 * User meta flag set on activation.
	 */
	public const META_KEY = '_breefer_welcome_redirect';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
	}

	/**
	 * Redirect once, after activation, and never during AJAX or bulk activation.
	 *
	 * @return void
	 */
	public function maybe_redirect(): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 || ! get_user_meta( $user_id, self::META_KEY, true ) ) {
			return;
		}

		delete_user_meta( $user_id, self::META_KEY );

		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$bulk = isset( $_GET['activate-multi'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( (string) $_GET['activate-multi'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		if ( '' !== $bulk ) {
			return;
		}

		$page = isset( $_GET['page'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		if ( '' !== $page || ! Capabilities::can_manage() ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=breefer' ) );
		exit;
	}
}
