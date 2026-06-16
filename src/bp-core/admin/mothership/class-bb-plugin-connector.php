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

		// Plugin basename for GroundLevel update integration (per the package README's
		// connection setup). This is inert until the main plugin file declares an
		// `Update URI: <pluginId>` header — adding that header would opt BuddyBoss Platform
		// into Mothership-driven plugin updates, which is a separate product decision.
		// productId is intentionally left empty: GroundLevel's UpdateService falls back to
		// the plugin slug (the dynamic plugin ID), which is exactly what BuddyBoss activates against.
		if ( function_exists( 'buddypress' ) && isset( buddypress()->basename ) ) {
			$this->pluginFile = buddypress()->basename;
		}
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
	 * Clear the BuddyBoss license-details and add-ons caches for the current plugin ID.
	 *
	 * Both caches are keyed by the current dynamic plugin ID, so this is called on each side
	 * of a plugin-ID change (before, to purge the OLD ID's caches; after, to purge the NEW
	 * ID's caches) — the two calls clear different keys, they are not redundant.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private static function clear_all_caches(): void {
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}
	}

	/**
	 * Set the dynamic plugin ID.
	 *
	 * @param string $pluginId The plugin ID to store.
	 */
	public function setDynamicPluginId( string $pluginId ): void {
		// Purge caches scoped to the OLD plugin ID before changing.
		self::clear_all_caches();

		update_option( 'buddyboss_dynamic_plugin_id', $pluginId );
		$this->pluginId = $pluginId;

		// Purge caches scoped to the NEW plugin ID after changing.
		self::clear_all_caches();
	}

	/**
	 * Clear the dynamic plugin ID.
	 */
	public function clearDynamicPluginId(): void {
		// Purge caches scoped to the OLD plugin ID before clearing.
		self::clear_all_caches();

		delete_option( 'buddyboss_dynamic_plugin_id' );
		$this->pluginId = PLATFORM_EDITION;

		// Purge caches scoped to the default plugin ID after clearing.
		self::clear_all_caches();
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
	 * Sets the license activation status option.
	 *
	 * Overrides {@see AbstractPluginConnection::setLicenseActivationStatus()} so the
	 * BuddyBoss option name (`{pluginId}_license_activation_status`) is preserved —
	 * the GroundLevel 7.3.1 base defaults to `{pluginId}_license_active`, which would
	 * orphan every existing activation. Also clears BuddyBoss license/add-on caches.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param boolean $status The status to update.
	 * @return boolean Whether the option was updated successfully.
	 */
	public function setLicenseActivationStatus( bool $status ): bool {
		$pluginId = $this->getCurrentPluginId();
		$updated  = update_option( $pluginId . '_license_activation_status', $status );

		// Clear license details + add-ons caches when activation status changes.
		self::clear_all_caches();

		return (bool) $updated;
	}

	/**
	 * Updates the license activation status option.
	 *
	 * Backward-compatible alias for {@see self::setLicenseActivationStatus()} retained
	 * for existing BuddyBoss callers that expect a void return.
	 *
	 * @param boolean $status The status to update.
	 */
	public function updateLicenseActivationStatus( bool $status ): void {
		$this->setLicenseActivationStatus( $status );
	}

	/**
	 * Resolves the license key from storage.
	 *
	 * Overrides {@see AbstractPluginConnection::resolveLicenseKey()} so the
	 * GroundLevel 7.3.1 {@see Credentials} reads the dynamic-plugin-id-scoped option.
	 * The option name (`{pluginId}_license_key`) matches the base default; the
	 * override exists only to honor the BuddyBoss dynamic plugin ID.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The license key.
	 */
	public function resolveLicenseKey(): string {
		$pluginId = $this->getCurrentPluginId();
		return (string) get_option( $pluginId . '_license_key', '' );
	}

	/**
	 * Stores the license key.
	 *
	 * Overrides {@see AbstractPluginConnection::storeLicenseKey()} so the GroundLevel
	 * 7.3.1 {@see Credentials} writes the dynamic-plugin-id-scoped option.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $licenseKey The license key to store.
	 * @return boolean Whether the option was updated successfully.
	 */
	public function storeLicenseKey( string $licenseKey ): bool {
		$pluginId = $this->getCurrentPluginId();
		return (bool) update_option( $pluginId . '_license_key', $licenseKey );
	}

	/**
	 * Gets the license key option.
	 *
	 * Convenience accessor retained for BuddyBoss callers (e.g. the add-ons manager
	 * license gate in {@see BB_Addons_Manager::render_addons_html()}).
	 *
	 * @return string The license key.
	 */
	public function getLicenseKey(): string {
		return $this->resolveLicenseKey();
	}

	/**
	 * Updates the license key option.
	 *
	 * Backward-compatible alias for {@see self::storeLicenseKey()} retained for
	 * existing BuddyBoss callers that expect a void return.
	 *
	 * @param string $licenseKey The license key to update.
	 */
	public function updateLicenseKey( string $licenseKey ): void {
		$this->storeLicenseKey( $licenseKey );
	}

	/**
	 * Gets the domain option.
	 *
	 * @return string The domain.
	 */
	public function getDomain(): string {
		return wp_parse_url( get_home_url(), PHP_URL_HOST );
	}
}
