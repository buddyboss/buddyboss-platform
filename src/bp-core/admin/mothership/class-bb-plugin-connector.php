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
	 * Option holding the licence key under a name that never changes.
	 *
	 * The per-SKU option (`{plugin_id}_license_key`) is addressed by a mutable
	 * id, so changing or clearing that id strands the key. This mirror is the
	 * durable copy; the per-SKU option is kept in step for backwards
	 * compatibility with anything reading it directly.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const STABLE_LICENSE_KEY_OPTION = 'buddyboss_license_key';

	/**
	 * Marks that the one-off recovery scan has already run.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const RECOVERY_FLAG_OPTION = 'buddyboss_license_key_recovered';

	/**
	 * Gets the license key option.
	 *
	 * Reads the per-SKU option first, then the stable mirror, then attempts a
	 * one-off recovery for sites stranded before the mirror existed.
	 *
	 * @return string The license key.
	 */
	public function getLicenseKey(): string {
		$pluginId    = $this->getCurrentPluginId();
		$license_key = (string) get_option( $pluginId . '_license_key', '' );

		if ( '' !== $license_key ) {
			return $license_key;
		}

		$license_key = (string) get_option( self::STABLE_LICENSE_KEY_OPTION, '' );

		if ( '' !== $license_key ) {
			return $license_key;
		}

		return $this->recoverStrandedLicenseKey();
	}

	/**
	 * Updates the license key option.
	 *
	 * Writes both the per-SKU option and the stable mirror so a later id change
	 * cannot strand the key. An empty value clears both, so resets stay clean.
	 *
	 * @param string $licenseKey The license key to update.
	 */
	public function updateLicenseKey( string $licenseKey ): void {
		$pluginId = $this->getCurrentPluginId();
		update_option( $pluginId . '_license_key', $licenseKey );

		if ( '' === $licenseKey ) {
			delete_option( self::STABLE_LICENSE_KEY_OPTION );
			delete_option( self::RECOVERY_FLAG_OPTION );

			return;
		}

		update_option( self::STABLE_LICENSE_KEY_OPTION, $licenseKey );
	}

	/**
	 * Recover a licence key stranded under a superseded plugin id.
	 *
	 * `setDynamicPluginId()` leaves the previous `{old_id}_license_key` row in
	 * place, and `clearDynamicPluginId()` — called from the licence-migration
	 * error paths — reverts reads to PLATFORM_EDITION, which holds no key. Both
	 * leave a licensed site reporting as unlicensed and being DRM-nagged.
	 *
	 * Runs at most once: the result is copied into the stable mirror, and a flag
	 * prevents rescanning. The scan is anchored to the `bb-` prefix because
	 * sites routinely store other vendors' keys under the same `_license_key`
	 * suffix, and those must never be read.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string Recovered licence key, or an empty string.
	 */
	private function recoverStrandedLicenseKey(): string {
		if ( get_option( self::RECOVERY_FLAG_OPTION, false ) ) {
			return '';
		}

		global $wpdb;

		// Record the attempt before scanning so a failure cannot loop.
		update_option( self::RECOVERY_FLAG_OPTION, 1 );

		$like = $wpdb->esc_like( 'bb-' ) . '%' . $wpdb->esc_like( '_license_key' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prefix-anchored, parameterized, runs at most once per site.
		$license_key = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value != '' ORDER BY option_id DESC LIMIT 1", $like ) );

		$license_key = is_string( $license_key ) ? trim( $license_key ) : '';

		if ( '' === $license_key ) {
			return '';
		}

		// Promote it so every later read resolves without another scan.
		update_option( self::STABLE_LICENSE_KEY_OPTION, $license_key );

		return $license_key;
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
