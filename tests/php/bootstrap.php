<?php
/**
 * PHPUnit bootstrap.
 *
 * The suite is split in two layers:
 * - pure unit tests for the renderer, registry and style generator;
 * - WordPress integration tests, which load the core test suite when
 *   WP_TESTS_DIR (or wp-phpunit) is available.
 *
 * @package Breefer
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$breefer_wp_tests = getenv( 'WP_TESTS_DIR' );

if ( false === $breefer_wp_tests || '' === $breefer_wp_tests ) {
	$breefer_wp_tests = dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! is_readable( $breefer_wp_tests . '/includes/functions.php' ) ) {
	return;
}

require_once $breefer_wp_tests . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require_once dirname( __DIR__, 2 ) . '/breefer.php';
	}
);

require_once $breefer_wp_tests . '/includes/bootstrap.php';
