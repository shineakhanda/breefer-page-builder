<?php
/**
 * Plugin Name:       Breefer
 * Plugin URI:        https://breefer.dev/
 * Description:       A performance-first, developer-friendly page builder with native WooCommerce support, custom React blocks, and zero frontend framework runtime.
 * Version:           0.6.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            Breefer
 * Author URI:        https://breefer.dev/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       breefer
 * Domain Path:       /languages
 *
 * Breefer is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version. It is distributed WITHOUT ANY WARRANTY; see the LICENSE file or
 * <https://www.gnu.org/licenses/gpl-3.0.html>.
 *
 * @package Breefer
 */

defined( 'ABSPATH' ) || exit;

define( 'BREEFER_VERSION', '0.6.0' );
define( 'BREEFER_DB_VERSION', '1' );
define( 'BREEFER_FILE', __FILE__ );
define( 'BREEFER_DIR', plugin_dir_path( __FILE__ ) );
define( 'BREEFER_URL', plugin_dir_url( __FILE__ ) );
define( 'BREEFER_BASENAME', plugin_basename( __FILE__ ) );
define( 'BREEFER_MIN_PHP', '8.2' );
define( 'BREEFER_MIN_WP', '6.2' );

/**
 * Print the PHP version requirement notice.
 *
 * Kept in the bootstrap file so it remains callable when the
 * namespaced plugin code cannot be parsed or loaded.
 *
 * @return void
 */
function breefer_php_version_notice(): void {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: required PHP version, 2: current PHP version. */
				__( 'Breefer requires PHP %1$s or newer. This server runs PHP %2$s.', 'breefer' ),
				BREEFER_MIN_PHP,
				PHP_VERSION
			)
		)
	);
}

if ( version_compare( PHP_VERSION, BREEFER_MIN_PHP, '<' ) ) {
	add_action( 'admin_notices', 'breefer_php_version_notice' );

	return;
}

$breefer_composer_autoload = BREEFER_DIR . 'vendor/autoload.php';

if ( is_readable( $breefer_composer_autoload ) ) {
	require_once $breefer_composer_autoload;
}

require_once BREEFER_DIR . 'includes/Autoloader.php';

\Breefer\Autoloader::register();

// Declare WooCommerce feature compatibility (HPOS and Cart/Checkout blocks).
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', BREEFER_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', BREEFER_FILE, true );
		}
	}
);

register_activation_hook( __FILE__, array( \Breefer\Activation::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \Breefer\Deactivation::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\Breefer\Plugin::instance()->boot();
	},
	5
);
