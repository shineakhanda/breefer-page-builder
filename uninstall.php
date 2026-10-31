<?php
/**
 * Breefer uninstall routine.
 *
 * Removes every option, transient, custom table, capability, cron event,
 * template post and generated upload for every site in the network.
 *
 * @package Breefer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove Breefer data for the current site.
 *
 * @return void
 */
function breefer_uninstall_site(): void {
	global $wpdb;

	// Templates and their meta.
	$post_ids = get_posts(
		array(
			'post_type'        => 'breefer_template',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'no_found_rows'    => true,
		)
	);

	foreach ( $post_ids as $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			continue;
		}

		wp_delete_post( (int) $post_id, true );
	}

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->esc_like( '_breefer' ) . '%'
		)
	);

	// Options and transients.
	$option_patterns = array(
		$wpdb->esc_like( 'breefer_' ) . '%',
		$wpdb->esc_like( '_transient_breefer_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_breefer_' ) . '%',
	);

	foreach ( $option_patterns as $pattern ) {
		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$pattern
			)
		);

		if ( ! is_array( $option_names ) ) {
			continue;
		}

		foreach ( $option_names as $option_name ) {
			delete_option( (string) $option_name );
		}
	}

	// User meta.
	$user_patterns = array(
		$wpdb->esc_like( 'breefer_' ) . '%',
		$wpdb->esc_like( '_breefer' ) . '%',
	);

	foreach ( $user_patterns as $pattern ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$pattern
			)
		);
	}

	// Custom tables.
	$tables = $wpdb->get_col(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$wpdb->esc_like( $wpdb->prefix . 'breefer_' ) . '%'
		)
	);

	if ( is_array( $tables ) ) {
		foreach ( $tables as $table ) {
			if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', (string) $table ) ) {
				continue;
			}

			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	// Scheduled events.
	$crons = _get_cron_array();

	if ( is_array( $crons ) ) {
		$changed = false;

		foreach ( $crons as $timestamp => $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}

			foreach ( array_keys( $hooks ) as $hook ) {
				$hook = (string) $hook;

				if ( str_starts_with( $hook, 'breefer/' ) || str_starts_with( $hook, 'breefer_' ) ) {
					unset( $crons[ $timestamp ][ $hook ] );
					$changed = true;
				}
			}

			if ( empty( $crons[ $timestamp ] ) ) {
				unset( $crons[ $timestamp ] );
			}
		}

		if ( $changed ) {
			_set_cron_array( $crons );
		}
	}

	// Capability.
	$role_names = wp_roles()->roles;

	if ( is_array( $role_names ) ) {
		foreach ( array_keys( $role_names ) as $role_slug ) {
			$role = get_role( (string) $role_slug );

			if ( null !== $role ) {
				$role->remove_cap( 'manage_breefer' );
			}
		}
	}

	// Generated uploads.
	$uploads = wp_upload_dir();

	if ( empty( $uploads['error'] ) ) {
		$directory = trailingslashit( (string) $uploads['basedir'] ) . 'breefer';

		if ( is_dir( $directory ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( WP_Filesystem() ) {
				global $wp_filesystem;

				if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
					$wp_filesystem->delete( $directory, true );
				}
			}
		}
	}
}

if ( is_multisite() ) {
	$breefer_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $breefer_sites as $breefer_site_id ) {
		switch_to_blog( (int) $breefer_site_id );
		breefer_uninstall_site();
		restore_current_blog();
	}

	global $wpdb;

	$site_patterns = array(
		$wpdb->esc_like( 'breefer_' ) . '%',
		$wpdb->esc_like( '_site_transient_breefer_' ) . '%',
		$wpdb->esc_like( '_site_transient_timeout_breefer_' ) . '%',
	);

	foreach ( $site_patterns as $pattern ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$pattern
			)
		);
	}
} else {
	breefer_uninstall_site();
}
