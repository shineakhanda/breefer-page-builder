<?php
/**
 * Admin menu registration.
 *
 * @package Breefer
 */

namespace Breefer\Admin;

use Breefer\PostTypes\Templates;
use Breefer\Utils\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Breefer admin menu.
 */
final class Menu {

	/**
	 * Dashboard page.
	 */
	private DashboardPage $dashboard;

	/**
	 * Settings page.
	 */
	private SettingsPage $settings;

	/**
	 * Constructor.
	 *
	 * @param DashboardPage $dashboard Dashboard page controller.
	 * @param SettingsPage  $settings  Settings page controller.
	 */
	public function __construct( DashboardPage $dashboard, SettingsPage $settings ) {
		$this->dashboard = $dashboard;
		$this->settings  = $settings;
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
	}

	/**
	 * Add the menu pages.
	 *
	 * @return void
	 */
	public function add_pages(): void {
		add_menu_page(
			__( 'Breefer', 'breefer' ),
			__( 'Breefer', 'breefer' ),
			Capabilities::MANAGE,
			'breefer',
			array( $this->dashboard, 'render' ),
			'dashicons-layout',
			58
		);

		add_submenu_page(
			'breefer',
			__( 'Dashboard', 'breefer' ),
			__( 'Dashboard', 'breefer' ),
			Capabilities::MANAGE,
			'breefer',
			array( $this->dashboard, 'render' )
		);

		add_submenu_page(
			'breefer',
			__( 'Templates', 'breefer' ),
			__( 'Templates', 'breefer' ),
			Capabilities::MANAGE,
			'edit.php?post_type=' . Templates::TYPE
		);

		add_submenu_page(
			'breefer',
			__( 'Add template', 'breefer' ),
			__( 'Add template', 'breefer' ),
			Capabilities::MANAGE,
			'post-new.php?post_type=' . Templates::TYPE
		);

		add_submenu_page(
			'breefer',
			__( 'New page', 'breefer' ),
			__( 'New page', 'breefer' ),
			Capabilities::MANAGE,
			'breefer-new-page',
			array( BuilderEntry::class, 'create_page_redirect' )
		);

		add_submenu_page(
			'breefer',
			__( 'Settings', 'breefer' ),
			__( 'Settings', 'breefer' ),
			Capabilities::MANAGE,
			'breefer-settings',
			array( $this->settings, 'render' )
		);
	}
}
