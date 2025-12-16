<?php
/**
 * BuddyBoss Platform - Mothership Loader
 *
 * Main loader class for BuddyBoss Mothership functionality.
 * Handles initialization of licensing and In-Product Notifications services.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since   BuddyBoss 2.14.0
 */

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;

/**
 * Main loader class for BuddyBoss Mothership functionality.
 *
 * This class follows the GroundLevel framework patterns for service registration,
 * container awareness, and hook configuration.
 */
class BB_Mothership_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var BB_Mothership_Loader|null
	 */
	private static $instance = null;

	/**
	 * Container for dependency injection.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Plugin connector instance.
	 *
	 * @var \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector
	 */
	private $pluginConnector;

	/**
	 * Get singleton instance.
	 *
	 * @return BB_Mothership_Loader
	 */
	public static function instance(): BB_Mothership_Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor (private to enforce singleton).
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the mothership functionality.
	 */
	private function init(): void {
		// Create the container.
		$this->container = new Container();

		// Create the plugin connector.
		$this->pluginConnector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();

		// Initialize the license manager to capture API headers.
		BB_License_Manager::init();

		// Initialize the mothership service.
		$this->initMothershipService();

		$this->initIPNService();

		// Set up hooks.
		$this->setupHooks();
	}

	/**
	 * Initialize the mothership service.
	 */
	private function initMothershipService(): void {
		// Create the mothership service.
		$mothershipService = new MothershipService( $this->container, $this->pluginConnector );

		// Load the mothership service dependencies.
		$mothershipService->load( $this->container );

		// Register the mothership service in the container.
		$this->container->addService(
			MothershipService::class,
			function () use ( $mothershipService ) {
				return $mothershipService;
			},
			true // Singleton.
		);
	}

	/**
	 * Initialize the In-Product Notifications service.
	 */
	private function initIPNService(): void {
		$plugin_id = $this->pluginConnector->getDynamicPluginId();

		// Set IPN Service parameters.
		$this->container->addParameter( IPNService::PRODUCT_SLUG, $plugin_id );
		$this->container->addParameter( IPNService::PREFIX, sanitize_title( $plugin_id ) );
		$this->container->addParameter( IPNService::MENU_SLUG, 'buddyboss-platform' );

		$this->container->addParameter(
			IPNService::RENDER_HOOK,
			'bb_admin_header_actions'
		);
		$this->container->addParameter(
			IPNService::THEME,
			array(
				'primaryColor'       => '#2271b1',
				'primaryColorDarker' => '#0a4b78',
			)
		);

		$this->container->addService(
			IPNService::class,
			static function ( Container $container ): IPNService {
				return new IPNService( $container );
			},
			true
		);
	}

	/**
	 * Setup WordPress hooks.
	 */
	private function setupHooks(): void {
		if ( is_admin() ) {
			// Register admin pages.
			add_action( 'admin_menu', array( $this, 'registerAdminPages' ), 99 );

			// Register license controller using BuddyBoss custom manager.
			add_action( 'admin_init', array( \BuddyBoss\Core\Admin\Mothership\BB_License_Manager::class, 'controller' ), 20 );

			// Register addons functionality using BuddyBoss custom manager.
			BB_Addons_Manager::loadHooks();

			// Register AJAX handlers.
			add_action( 'wp_ajax_bb_get_free_license', array( 'BuddyBoss\Core\Admin\Mothership\BB_License_Manager', 'ajax_get_free_license' ) );
			add_action( 'wp_ajax_bb_reset_license_settings', array( 'BuddyBoss\Core\Admin\Mothership\BB_License_Manager', 'ajax_reset_license_settings' ) );
		}

		$plugin_id = $this->pluginConnector->getDynamicPluginId();

		// Handle license status changes.
		add_action( $plugin_id . '_license_status_changed', array( $this, 'handleLicenseStatusChange' ), 10, 2 );

		// For local development - disable SSL verification if needed.
		if ( defined( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) && constant( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) ) {
			add_filter( 'https_ssl_verify', '__return_false' );
		}
	}

	/**
	 * Register admin pages.
	 */
	public function registerAdminPages(): void {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Register License page.
		\BuddyBoss\Core\Admin\Mothership\BB_License_Page::register();

		// Register Addons page.
		\BuddyBoss\Core\Admin\Mothership\BB_Addons_Page::register();
	}

	/**
	 * Handle license status changes.
	 *
	 * @param bool  $isActive License active status.
	 * @param mixed $response API response.
	 */
	public function handleLicenseStatusChange( bool $isActive, $response ): void {
		$plugin_id = $this->pluginConnector->getDynamicPluginId();

		if ( ! $isActive ) {
			// License is no longer active.
			$this->pluginConnector->updateLicenseActivationStatus( false );

			// Clear cached data.
			delete_transient( $plugin_id . '-mosh-products' );
			delete_transient( $plugin_id . '-mosh-addons-update-check' );

			// Log the deactivation (sanitized - no sensitive data).
			$log_message = 'BuddyBoss license deactivated';
			if ( is_object( $response ) && method_exists( $response, '__get' ) ) {
				$error_code = $response->__get( 'errorCode' );
				if ( $error_code ) {
					$log_message .= sprintf( ' - Error code: %d', $error_code );
				}
			}
			bb_error_log( $log_message, true );
		} else {
			// License is active - ensure status is updated.
			$this->pluginConnector->updateLicenseActivationStatus( true );
		}
	}

	/**
	 * Get the container.
	 *
	 * @return Container The container instance.
	 */
	public function getContainer(): Container {
		return $this->container;
	}

	/**
	 * Refresh the plugin connector with updated plugin ID.
	 * This should be called after the plugin ID changes.
	 */
	public function refreshPluginConnector(): void {
		// The plugin connector will automatically use the updated plugin ID from the database option on the next request.
	}

	/**
	 * Migrate legacy license data from old storage to Mothership.
	 *
	 * This method checks for legacy license data stored in options
	 * and attempts to migrate it to the new Mothership system.
	 */
	public static function migrate_legacy_license(): void {
		if ( ! is_admin() ) {
			return; // Only run migration in admin context.
		}

		$network_activated = false;
		/**
		 * This is added to give the backward compatibility.
		 */
		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active_for_network( buddypress()->basename ) ) {
				$network_activated = true;
			}
		}

		if ( $network_activated && true === (bool) get_site_option( 'bb_mothership_licenses_migrated', false ) ) {
			return;
		} elseif ( ! $network_activated && true === (bool) get_option( 'bb_mothership_licenses_migrated', false ) ) {
			return;
		}

		$legacy_licences = get_option( 'bboss_updater_saved_licenses', array() );

		if ( $network_activated ) {
			$legacy_licences = get_site_option( 'bboss_updater_saved_licenses', array() );
		}

		if ( empty( $legacy_licences ) ) {
			return;
		}

		$migrated_licence = array();

		if ( isset( $legacy_licences['buddyboss_theme'] ) ) {
			$migrated_licence['buddyboss_theme'] = $legacy_licences['buddyboss_theme'];
		}

		if ( isset( $legacy_licences['bb_platform_pro'] ) ) {
			$migrated_licence['bb_platform_pro'] = $legacy_licences['bb_platform_pro'];
		}

		if ( empty( $migrated_licence ) ) {
			return;
		}

		$instance        = new self();
		$pluginConnector = $instance->getContainer()->get( AbstractPluginConnection::class );
		$plugin_id       = $pluginConnector->getDynamicPluginId();

		$current_status = $pluginConnector->getLicenseActivationStatus();

		if ( $current_status ) {
			return;
		}

		foreach ( $migrated_licence as $plugin_key => $license_data ) {
			if (
				empty( $license_data['license_key'] ) ||
				empty( $license_data['status'] ) ||
				empty( $license_data['software_product_id'] )
			) {
				continue;
			}

			$current_status = $pluginConnector->getLicenseActivationStatus();

			if ( $current_status ) {
				break;
			}

			$software_id = $license_data['software_product_id'];
			$plugin_id   = self::mapSoftwareIdToPluginId( $software_id );

			if ( PLATFORM_EDITION !== $plugin_id ) {
				$pluginConnector->setDynamicPluginId( $plugin_id );
				$domain = Credentials::getActivationDomain();

				// Check if we're being rate limited before attempting migration activation.
				if ( self::isRateLimitedForMigration( $network_activated ) ) {
					continue; // Skip this license migration.
				}

				// Translators: %s is the response error message.
				$errorHtml = esc_html__( 'Migrate License activation failed: %s', 'buddyboss' );

				try {
					$response = LicenseActivations::activate( $plugin_id, $license_data['license_key'], $domain );
				} catch ( \Exception $e ) {
					bb_error_log( sprintf( $errorHtml, $e->getMessage() ), true );
					// Clear the dynamic plugin ID on exception to prevent orphaned state.
					$pluginConnector->clearDynamicPluginId();
					continue;
				}

				if ( $response instanceof Response && ! $response->isError() ) {
					try {
						Credentials::storeLicenseKey( $license_data['license_key'] );
						$pluginConnector->updateLicenseActivationStatus( true );

						// Clear add-ons cache to force refresh.
						$pluginId = $pluginConnector->pluginId;
						delete_transient( $pluginId . '-mosh-products' );
						delete_transient( $pluginId . '-mosh-addons-update-check' );
						if ( $network_activated ) {
							update_site_option( 'bb_mothership_licenses_migrated', true );
						} else {
							update_option( 'bb_mothership_licenses_migrated', true );
						}
					} catch ( \Exception $e ) {
						// Log the exception.
						bb_error_log( 'Error storing migrated license key: ' . $e->getMessage(), true );
					}
				} else {
					// Migration failed - clear the dynamic plugin ID to prevent orphaned state.
					$errorCode    = $response->__get( 'errorCode' );
					$errorMessage = $response->__get( 'error' );

					bb_error_log( sprintf( 'BuddyBoss License Migration Failed (Code: %d): %s', $errorCode, $errorMessage ), true );

					// If it's a 422 product mismatch, definitely clear the dynamic plugin ID.
					if ( 422 === $errorCode ) {
						$pluginConnector->clearDynamicPluginId();
						bb_error_log( 'BuddyBoss: Cleared dynamic plugin ID due to 422 product mismatch during migration', true );
					} elseif ( 429 !== $errorCode ) {
						// For errors other than rate limiting, also clear the dynamic plugin ID.
						// (Rate limit might be temporary, so we keep the plugin ID for retry).
						$pluginConnector->clearDynamicPluginId();
					}
				}
			}
		}
	}

	/**
	 * Check if migration is currently rate limited.
	 *
	 * @param bool $network_activated Whether the plugin is network activated.
	 *
	 * @return bool True if rate limited, false otherwise.
	 */
	private static function isRateLimitedForMigration( bool $network_activated ): bool {
		$rateLimitData = $network_activated ? get_site_transient( 'bb_license_rate_limit' ) : get_transient( 'bb_license_rate_limit' );

		if ( ! $rateLimitData || ! is_array( $rateLimitData ) ) {
			return false;
		}

		$resetTime   = isset( $rateLimitData['reset'] ) ? (int) $rateLimitData['reset'] : 0;
		$currentTime = time();

		if ( $resetTime > 0 && $currentTime < $resetTime ) {
			$waitMinutes = ceil( ( $resetTime - $currentTime ) / 60 );
			bb_error_log(
				sprintf(
					'BuddyBoss: Skipping migration activation - rate limited for %d more minutes (reset: %s)',
					$waitMinutes,
					date( 'Y-m-d H:i:s', $resetTime )
				),
				true
			);
			return true;
		}

		return false;
	}

	/**
	 * Map legacy software product ID to new plugin ID.
	 *
	 * @param string $software_id The legacy software product ID.
	 *
	 * @return string The mapped plugin ID.
	 */
	private static function mapSoftwareIdToPluginId( string $software_id ): string {
		$mapping = array(
			'BB_PLATFORM_PRO_1S'  => 'bb-platform-pro-1-site',
			'BB_PLATFORM_PRO_2S'  => 'bb-platform-pro-2-sites',
			'BB_PLATFORM_PRO_5S'  => 'bb-platform-pro-5-sites',
			'BB_PLATFORM_FREE'    => 'bb-platform-free',
			'BB_PLATFORM_PRO_10S' => 'bb-platform-pro-10-sites',
			'BB_THEME_1S'         => 'bb-web',
			'BUDDYBOSS_THEME_1S'  => 'bb-web',
			'BB_THEME_2S'         => 'bb-web-2-sites',
			'BB_THEME_5S'         => 'bb-web-5-sites',
			'BUDDYBOSS_THEME_5S'  => 'bb-web-5-sites',
			'BB_THEME_10S'        => 'bb-web-10-sites',
			'BB_THEME_20S'        => 'bb-web-20-sites',
			'BUDDYBOSS_THEME_20S' => 'bb-web-20-sites',
		);

		return isset( $mapping[ $software_id ] ) ? $mapping[ $software_id ] : PLATFORM_EDITION;
	}
}
