<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Plugin Connector class for BuddyBoss Platform.
 *
 * This class follows the GroundLevel AbstractPluginConnection pattern
 * for managing plugin-specific data and API connections.
 */
class BB_Plugin_Connector extends AbstractPluginConnection {

	/**
	 * Constructor for the BB_Plugin_Connector class.
	 */
	public function __construct() {
		$this->pluginId     = $this->getDynamicPluginId();
		$this->pluginPrefix = 'buddyboss';
	}

	/**
	 * Get the dynamic plugin ID from stored option or default.
	 *
	 * @return string The plugin ID.
	 */
	public function getDynamicPluginId(): string {
		$storedPluginId = get_option( 'buddyboss_dynamic_plugin_id', PLATFORM_EDITION );
		return ! empty( $storedPluginId ) ? $storedPluginId : PLATFORM_EDITION;
	}

	/**
	 * Set the dynamic plugin ID.
	 *
	 * @param string $pluginId The plugin ID to store.
	 */
	public function setDynamicPluginId( string $pluginId ): void {
		// Clear caches with old plugin ID before changing.
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}

		update_option( 'buddyboss_dynamic_plugin_id', $pluginId );
		$this->pluginId = $pluginId;

		// Clear caches with new plugin ID after changing.
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}
	}

	/**
	 * Clear the dynamic plugin ID.
	 */
	public function clearDynamicPluginId(): void {
		// Clear caches with old plugin ID before clearing.
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}

		delete_option( 'buddyboss_dynamic_plugin_id' );
		$this->pluginId = PLATFORM_EDITION;

		// Clear caches with default plugin ID after clearing.
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}
	}

	/**
	 * Get the current plugin ID.
	 *
	 * @return string The current plugin ID.
	 */
	public function getCurrentPluginId(): string {
		return $this->pluginId;
	}

	/**
	 * Gets the license activation status option.
	 *
	 * @return boolean The license activation status.
	 */
	public function getLicenseActivationStatus(): bool {
		$pluginId = $this->getCurrentPluginId();
		$status   = get_option( $pluginId . '_license_activation_status', false );
		return (bool) $status;
	}

	/**
	 * Updates the license activation status option.
	 *
	 * @param boolean $status The status to update.
	 */
	public function updateLicenseActivationStatus( bool $status ): void {
		$pluginId = $this->getCurrentPluginId();
		update_option( $pluginId . '_license_activation_status', $status );

		// Clear license details cache when activation status changes.
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}

		// Clear product add-ons cache when license status changes.
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
	}

	/**
	 * Gets the license key option.
	 *
	 * @return string The license key.
	 */
	public function getLicenseKey(): string {
		$pluginId    = $this->getCurrentPluginId();
		$license_key = get_option( $pluginId . '_license_key', '' );
		return (string) $license_key;
	}

	/**
	 * Updates the license key option.
	 *
	 * @param string $licenseKey The license key to update.
	 */
	public function updateLicenseKey( string $licenseKey ): void {
		$pluginId = $this->getCurrentPluginId();
		update_option( $pluginId . '_license_key', $licenseKey );
	}

	/**
	 * Gets the domain option.
	 *
	 * @return string The domain.
	 */
	public function getDomain(): string {
		return wp_parse_url( get_home_url(), PHP_URL_HOST );
	}

	/**
	 * Debug method to get current status.
	 *
	 * @return array Debug information.
	 */
	public function getDebugInfo(): array {
		return array(
			'plugin_id'                => $this->getCurrentPluginId(),
			'plugin_prefix'            => $this->pluginPrefix,
			'license_key'              => $this->getLicenseKey(),
			'license_activated'        => $this->getLicenseActivationStatus(),
			'domain'                   => $this->getDomain(),
			'api_base_url'             => defined( strtoupper( $this->getCurrentPluginId() . '_MOTHERSHIP_API_BASE_URL' ) )
				? constant( strtoupper( $this->getCurrentPluginId() . '_MOTHERSHIP_API_BASE_URL' ) )
				: 'https://licenses.caseproof.com/api/v1/',
			'dynamic_plugin_id_stored' => get_option( 'buddyboss_dynamic_plugin_id', PLATFORM_EDITION ),
		);
	}
}
