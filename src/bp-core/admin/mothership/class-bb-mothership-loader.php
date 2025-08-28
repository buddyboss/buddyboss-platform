<?php

use GroundLevel\Mothership\Manager\LicenseManager;
use GroundLevel\Mothership\Manager\AddonsManager;

/**
 * BuddyBoss Platform Mothership Loader
 *
 * Main class that initializes the mothership system for BuddyBoss Platform
 */
class BB_Mothership_Loader {

	/**
	 * Initialize the mothership system
	 */
	public static function init() {
		// Check if we're in admin and user has permissions.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Load required files.
		if ( ! self::load_files() ) {
			return;
		}

		// Initialize the mothership controller.
		BB_Mothership_Controller::init();

		// Add admin menu hooks.
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 20 );
	}

	/**
	 * Load required files
	 */
	private static function load_files() {
		$mothership_dir = __DIR__;

		// Load core classes.
		require_once $mothership_dir . '/class-bb-mothership-plugin-connector.php';
		require_once $mothership_dir . '/class-bb-mothership-controller.php';
		require_once $mothership_dir . '/class-bb-mothership-admin.php';
		require_once $mothership_dir . '/class-bb-mothership-addons.php';
		require_once $mothership_dir . '/class-bb-mothership-hooks.php';

		return true;
	}

	/**
	 * Add admin menu items
	 */
	public static function add_admin_menu() {
		// Add mothership menu items under BuddyBoss main menu.
		add_submenu_page(
			'buddyboss-platform',  // Parent slug (BuddyBoss main menu).
			__( 'BuddyBoss Platform License', 'buddyboss' ),
			__( 'Platform License', 'buddyboss' ),
			'manage_options',
			'bb-platform-mothership-license',
			array( BB_Mothership_Admin::instance(), 'render_page' )
		);

		add_submenu_page(
			'buddyboss-platform',  // Parent slug (BuddyBoss main menu).
			__( 'BuddyBoss Platform Add-ons', 'buddyboss' ),
			__( 'Platform Add-ons', 'buddyboss' ),
			'manage_options',
			'bb-platform-mothership-addons',
			array( BB_Mothership_Addons::instance(), 'render_page' )
		);
	}

	/**
	 * Check if mothership is available
	 */
	public static function is_mothership_available() {
		// Ensure autoloader is loaded.
		if ( ! class_exists( 'GroundLevel\Container\Container' ) ) {
			return false;
		}

		return class_exists( 'GroundLevel\Mothership\Service' ) &&
				interface_exists( 'GroundLevel\Container\Contracts\StaticContainerAwareness' );
	}

	/**
	 * Get mothership status
	 */
	public static function get_mothership_status() {
		if ( ! self::is_mothership_available() ) {
			return 'unavailable';
		}

		try {
			$container = BB_Mothership_Controller::getContainer();
			if ( ! $container ) {
				return 'not_initialized';
			}

			// Use static LicenseManager directly.
			$status = LicenseManager::checkLicenseStatus();

			return $status ? 'active' : 'inactive';
		} catch ( Exception $e ) {
			return 'error';
		}
	}

	/**
	 * Get license information
	 */
	public static function get_license_info() {
		if ( ! self::is_mothership_available() ) {
			return array();
		}

		try {
			$container = BB_Mothership_Controller::getContainer();
			if ( ! $container ) {
				return array();
			}

			// Use static LicenseManager directly.
			// Note: LicenseManager doesn't have a getLicenseInfo method, so we'll return basic info.
			$status = LicenseManager::checkLicenseStatus();
			return array(
				'status'  => $status ? 'active' : 'inactive',
				'checked' => true,
			);
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Get available addons
	 */
	public static function get_addons() {
		if ( ! self::is_mothership_available() ) {
			return array();
		}

		try {
			$container = BB_Mothership_Controller::getContainer();
			if ( ! $container ) {
				return array();
			}

			// Use static AddonsManager directly.
			return AddonsManager::getAddons() ?: array();
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Display admin notice if mothership is not available
	 */
	public static function admin_notice_mothership_unavailable() {
		if ( ! self::is_mothership_available() && current_user_can( 'manage_options' ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						esc_html__( 'BuddyBoss Platform mothership system is not available. Please ensure the required dependencies are installed. %1$sContact support%2$s for assistance.', 'buddyboss' ),
						'<a href="https://buddyboss.com/support/" target="_blank">',
						'</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
