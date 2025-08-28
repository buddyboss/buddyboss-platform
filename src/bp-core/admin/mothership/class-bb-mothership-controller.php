<?php

use GroundLevel\Container\Contracts\StaticContainerAwareness;
use GroundLevel\Container\Container;
use GroundLevel\Mothership\Service as MothershipService;
use GroundLevel\Mothership\Manager\LicenseManager;
use GroundLevel\Mothership\Manager\AddonsManager;
use GroundLevel\Mothership\Api\Request\LicenseActivations;

/**
 * BuddyBoss Platform Mothership Controller
 *
 * Handles the initialization and management of the Mothership service
 * for BuddyBoss Platform plugin.
 */
class BB_Mothership_Controller implements StaticContainerAwareness {

	/**
	 * Container instance
	 *
	 * @var Container
	 */
	protected static $container;

	/**
	 * Initialize the mothership controller
	 */
	public static function init() {
		// Initialize the container.
		self::init_container();

		// Initialize mothership service.
		self::init_mothership();

		// Set up admin pages.
		self::setup_admin_pages();

		// Add hooks.
		self::add_hooks();
	}

	/**
	 * Initialize the container
	 */
	private static function init_container() {
		self::$container = new Container();

		// Register the mothership service.
		$plugin = new BB_Mothership_Plugin_Connector();

		self::$container->addService(
			MothershipService::class,
			static function () use ( $plugin ): MothershipService {
				return new MothershipService( self::$container, $plugin );
			}
		);

		// Set container for admin pages and managers.
		BB_Mothership_Admin::setContainer( self::$container );
		BB_Mothership_Addons::setContainer( self::$container );
		BB_Mothership_Hooks::setContainer( self::$container );

		// Set container for GroundLevel managers.
		LicenseManager::setContainer( self::$container );
		AddonsManager::setContainer( self::$container );
	}

	/**
	 * Initialize the mothership service
	 */
	private static function init_mothership() {
		// Initialize the mothership service.
		$mothershipService = self::$container->get( MothershipService::class );

		// Set up the service.
		$mothershipService->load( self::$container );

		// Migrate legacy license data if needed.
		self::migrate_legacy_license_data();
	}

	/**
	 * Set up admin pages
	 */
	private static function setup_admin_pages() {
		// Admin pages are registered in BB_Mothership_Loader::add_admin_menu()
		// No need to register them here to avoid duplicates.
	}

	/**
	 * Add hooks
	 */
	private static function add_hooks() {
		// Add mothership hooks.
		BB_Mothership_Hooks::add_hooks();
	}

	/**
	 * Migrate legacy license data to mothership
	 */
	private static function migrate_legacy_license_data() {
		// Check if migration is needed.
		$migration_completed = get_option( 'bb_platform_mothership_migration_completed', false );

		if ( $migration_completed ) {
			return;
		}

		// Get legacy license data.
		$legacy_license_key = get_option( 'bb_platform_license_key', '' );

		if ( ! empty( $legacy_license_key ) ) {
			try {
				// Use static LicenseManager.
				$result = LicenseManager::activateLicense( $legacy_license_key );

				if ( $result ) {
					// Mark migration as completed.
					update_option( 'bb_platform_mothership_migration_completed', true );
				}
			} catch ( Exception $e ) {
				// Log error but don't fail.
				error_log( 'BuddyBoss Platform Mothership Migration Error: ' . $e->getMessage() );
			}
		} else {
			// No legacy data to migrate, mark as completed.
			update_option( 'bb_platform_mothership_migration_completed', true );
		}
	}

	/**
	 * Get the container instance
	 *
	 * @return Container
	 */
	public static function getContainer(): Container {
		return self::$container;
	}

	/**
	 * Set the container instance
	 *
	 * @param Container $container
	 */
	public static function setContainer( Container $container ): void {
		self::$container = $container;
	}
}
