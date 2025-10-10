<?php

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
	 * Constructor.
	 */
	public function __construct() {
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

			// Log the deactivation.
			error_log( 'BuddyBoss license deactivated: ' . print_r( $response, true ) );
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
		// The plugin connector will automatically use the updated plugin ID
		// from the database option on the next request.
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
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
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

			if ( 'BB_PLATFORM_PRO_1S' === $software_id ) {
				$plugin_id = 'bb-platform-pro-1-site';
			} elseif ( 'BB_PLATFORM_PRO_2S' === $software_id ) {
				$plugin_id = 'bb-platform-pro-2-sites';
			} elseif ( 'BB_PLATFORM_PRO_5S' === $software_id ) {
				$plugin_id = 'bb-platform-pro-5-sites';
			} elseif ( 'BB_PLATFORM_FREE' === $software_id ) {
				$plugin_id = 'bb-platform-free';
			} elseif ( 'BB_PLATFORM_PRO_10S' === $software_id ) {
				$plugin_id = 'bb-platform-pro-10-sites';
			} elseif (
				'BB_THEME_1S' === $software_id ||
				'BUDDYBOSS_THEME_1S' === $software_id
			) {
				$plugin_id = 'bb-web';
			} elseif ( 'BB_THEME_2S' === $software_id ) {
				$plugin_id = 'bb-web-2-sites';
			} elseif (
				'BB_THEME_5S' === $software_id ||
				'BUDDYBOSS_THEME_5S' === $software_id
			) {
				$plugin_id = 'bb-web-5-sites';
			} elseif ( 'BB_THEME_10S' === $software_id ) {
				$plugin_id = 'bb-web-10-sites';
			} elseif (
				'BB_THEME_20S' === $software_id ||
				'BUDDYBOSS_THEME_20S' === $software_id
			) {
				$plugin_id = 'bb-web-20-sites';
			}

			if ( $plugin_id !== PLATFORM_EDITION ) {
				$pluginConnector->setDynamicPluginId( $plugin_id );
				$domain   = Credentials::getActivationDomain();

				// Translators: %s is the response error message.
				$errorHtml = esc_html__( 'Migrate License activation failed: %s', 'buddyboss' );

				try {
					$response = LicenseActivations::activate( $plugin_id, $license_data['license_key'], $domain );
				} catch ( \Exception $e ) {
					error_log( sprintf( $errorHtml, $e->getMessage() ) );
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
						error_log( 'Error storing migrated license key: ' . $e->getMessage() );
					}
				} else {
					error_log( $response->__get('error') );
				}
			}
		}
	}
}
