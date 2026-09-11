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

		// The Mothership product ID is the dynamic plugin ID (edition) the license was
		// activated against. GroundLevel 9.x reads it directly: the add-ons manager fetches
		// add-ons as relations of this product, and the license manager uses it for
		// activation/edition checks — so it must never be left empty.
		$this->productId = $this->pluginId;

		// Plugin basename for GroundLevel update integration (per the package README's
		// connection setup). The `Update URI: https://buddyboss-platform` header in
		// bp-loader.php opts BuddyBoss Platform into Mothership-driven plugin updates.
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
		$this->pluginId  = $pluginId;
		$this->productId = $pluginId;

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
		$this->pluginId  = PLATFORM_EDITION;
		$this->productId = PLATFORM_EDITION;

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
	 * the GroundLevel 9.1.2 base defaults to `{pluginId}_license_active`, which would
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
	 * GroundLevel 9.1.2 {@see Credentials} reads the dynamic-plugin-id-scoped option.
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
	 * 9.1.2 {@see Credentials} writes the dynamic-plugin-id-scoped option.
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
	 * Resolves the license activation domain.
	 *
	 * Overrides {@see AbstractPluginConnection::resolveDomain()}, which returns only the
	 * host of the home URL. WordPress installs that live in a subdirectory (e.g.
	 * `example.com/community`) would otherwise share an activation identifier with a
	 * second install on the root domain, so activating both against the same license
	 * conflicts (PROD-9984). Appending the path gives each install its own identifier —
	 * the same format Caseproof's own products send, and the licensing server accepts
	 * the encoded slash. Root-domain installs are unaffected: the value stays the bare
	 * host, so existing activations keep matching.
	 *
	 * When BuddyBoss Platform is network-activated, the network home URL is used so
	 * every site in the network resolves the same domain for the shared license.
	 *
	 * A `BUDDYBOSS_DOMAIN` constant or environment variable still takes precedence via
	 * {@see \BuddyBossPlatform\GroundLevel\Mothership\Credentials::getDomain()}.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The activation domain (`host` or `host/path`).
	 */
	public function resolveDomain(): string {
		$home_url = $this->is_network_activated() ? network_home_url() : get_home_url();
		$host     = (string) wp_parse_url( $home_url, PHP_URL_HOST );

		if ( '' === $host ) {
			return '';
		}

		return $host . untrailingslashit( (string) wp_parse_url( $home_url, PHP_URL_PATH ) );
	}

	/**
	 * Gets the license activation domain.
	 *
	 * Backward-compatible alias for {@see self::resolveDomain()} retained for existing
	 * BuddyBoss callers. Consumer code should prefer
	 * {@see \BuddyBossPlatform\GroundLevel\Mothership\Credentials::getDomain()}, which
	 * also honors constant/environment overrides.
	 *
	 * @return string The activation domain.
	 */
	public function getDomain(): string {
		return $this->resolveDomain();
	}

	/**
	 * Gets the BuddyBoss account dashboard URL.
	 *
	 * GroundLevel links here from the add-ons grid ("Upgrade" cards for add-ons the
	 * current license does not include) and from the plugin update row when an update
	 * cannot be downloaded because of the license state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The account URL.
	 */
	public function getAccountUrl(): string {
		return 'https://www.buddyboss.com/my-account/';
	}

	/**
	 * Whether BuddyBoss Platform is network-activated on a multisite install.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	private function is_network_activated(): bool {
		if ( ! is_multisite() || ! function_exists( 'buddypress' ) || empty( buddypress()->basename ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( buddypress()->basename );
	}
}
