<?php
/**
 * Plugin container.
 *
 * @package Breefer
 */

namespace Breefer;

use Breefer\Admin\Assets;
use Breefer\Admin\BuilderEntry;
use Breefer\Admin\DashboardPage;
use Breefer\Admin\EditorPage;
use Breefer\Admin\Menu;
use Breefer\Admin\MetaBox;
use Breefer\Admin\SettingsPage;
use Breefer\Admin\ThemeBuilderPage;
use Breefer\Admin\WelcomeRedirect;
use Breefer\Compatibility\WooCommerce as WooCompatibility;
use Breefer\Frontend\Frontend;
use Breefer\PostTypes\Templates;
use Breefer\Rendering\Renderer;
use Breefer\Rendering\StyleGenerator;
use Breefer\Rendering\WidgetRegistry;
use Breefer\Rest\FormsController;
use Breefer\Rest\PreviewController;
use Breefer\Rest\TemplatesController;
use Breefer\Theme\ThemeBuilder;
use Breefer\Utils\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Boots and wires the plugin services.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 */
	private static ?Plugin $instance = null;

	/**
	 * Whether boot() already ran.
	 */
	private bool $booted = false;

	/**
	 * Booted services, addressable by id.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Retrieve the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor. Use instance().
	 */
	private function __construct() {}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( ! $this->requirements_met() ) {
			add_action( 'admin_notices', array( $this, 'requirements_notice' ) );

			return;
		}

		// Grant the menu capability after a file update that skipped activation.
		if ( get_option( 'breefer_version' ) !== BREEFER_VERSION ) {
			Capabilities::grant( 'administrator' );
			update_option( 'breefer_version', BREEFER_VERSION );
		}

		$registry  = new WidgetRegistry();
		$renderer  = new Renderer( $registry, new StyleGenerator() );
		$templates = new Templates();
		$frontend  = new Frontend( $renderer );
		$editor    = new EditorPage();
		$meta_box  = new MetaBox();
		$dashboard = new DashboardPage();
		$settings  = new SettingsPage();
		$menu      = new Menu( $dashboard, $settings );
		$assets    = new Assets();
		$welcome   = new WelcomeRedirect();
		$rest      = new TemplatesController();
		$woo       = new WooCompatibility();
		$entry     = new BuilderEntry();
		$theme     = new ThemeBuilder( $renderer );
		$preview   = new PreviewController( $registry );
		$forms     = new FormsController();
		$builder   = new ThemeBuilderPage();

		$this->services = array(
			'widgets'   => $registry,
			'renderer'  => $renderer,
			'templates' => $templates,
			'dashboard' => $dashboard,
			'settings'  => $settings,
			'editor'    => $editor,
			'menu'      => $menu,
			'assets'    => $assets,
			'welcome'   => $welcome,
			'rest'      => $rest,
			'woo'       => $woo,
			'entry'     => $entry,
			'theme'     => $theme,
			'preview'   => $preview,
			'forms'     => $forms,
			'builder'   => $builder,
		);

		$templates->register();
		$frontend->register();
		$woo->register();
		$entry->register();
		$theme->register();
		$preview->register();
		$forms->register();
		$builder->register();
		$rest->register();
		$editor->register();
		$meta_box->register();
		$settings->register();
		$menu->register();
		$assets->register();
		$welcome->register();

		/**
		 * Fires once Breefer has booted and its core services are registered.
		 *
		 * @param Plugin $plugin The plugin container.
		 */
		do_action( 'breefer/loaded', $this );
	}

	/**
	 * Retrieve a booted service.
	 *
	 * @param string $id Service id.
	 * @return object|null
	 */
	public function get( string $id ): ?object {
		return $this->services[ $id ] ?? null;
	}

	/**
	 * Load the plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'breefer', false, dirname( BREEFER_BASENAME ) . '/languages' );
	}

	/**
	 * Check the runtime requirements.
	 *
	 * @return bool
	 */
	private function requirements_met(): bool {
		$wp_version = (string) get_bloginfo( 'version' );

		return version_compare( PHP_VERSION, BREEFER_MIN_PHP, '>=' )
			&& version_compare( $wp_version, BREEFER_MIN_WP, '>=' );
	}

	/**
	 * Render the WordPress version requirement notice.
	 *
	 * @return void
	 */
	public function requirements_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: required WordPress version, 2: current WordPress version. */
					__( 'Breefer requires WordPress %1$s or newer. This site runs WordPress %2$s.', 'breefer' ),
					BREEFER_MIN_WP,
					(string) get_bloginfo( 'version' )
				)
			)
		);
	}
}
