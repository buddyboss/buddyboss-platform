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
		// connection setup). BuddyBoss Platform ships without an `Update URI` header, so the
		// update entry is injected into the update_plugins transient by
		// {@see BB_Mothership_Loader::inject_platform_update()}; this basename is the key it
		// is filed under.
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

		// The DRM "is this add-on licensed" decision is cached per request and is resolved
		// as early as `bp_loaded`, long before a licence is activated on `admin_init` 20.
		// Without this reset the DRM sweep at `admin_init` 25 re-reads the pre-activation
		// answer and skips cleanup, leaving the wp_bb_drm_events rows and their notices in
		// place until the next page load.
		if ( class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Addon' ) ) {
			\BuddyBoss\Core\Admin\DRM\BB_DRM_Addon::reset_licensed_cache();
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
	 *
	 * Deliberately does NOT clear the STABLE_LICENSE_KEY_OPTION mirror. Every
	 * caller except the explicit licence reset reaches this from a transient
	 * failure path — a rejected activation attempt or a failed legacy
	 * migration — where the previously activated key is still the customer's
	 * real licence and must survive, or the failure strands the site as
	 * unlicensed and DRM-nags a paying customer (the exact defect the mirror
	 * exists to fix). A failed activation never persists its candidate key,
	 * so the mirror only ever holds the last successfully activated one. The
	 * reset path is the one place that clears user intent, and it pairs this
	 * call with updateLicenseKey( '' ).
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
	 * Reads the per-SKU option first, then the stable mirror. Both hold the
	 * currently activated key. Superseded `{old_sku}_license_key` rows are
	 * deliberately never read — reporting a stale key is worse than reporting
	 * none, because it looks correct. The fallback lives here rather than in
	 * {@see self::getLicenseKey()} because `Credentials::getLicenseKey()` resolves
	 * through this method, so this is the only placement that also keeps the
	 * GroundLevel read path working across a plugin-id change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The license key.
	 */
	public function resolveLicenseKey(): string {
		$pluginId    = $this->getCurrentPluginId();
		$license_key = (string) get_option( $pluginId . '_license_key', '' );

		if ( '' !== $license_key ) {
			return $license_key;
		}

		return (string) get_option( self::STABLE_LICENSE_KEY_OPTION, '' );
	}

	/**
	 * Stores the license key.
	 *
	 * Overrides {@see AbstractPluginConnection::storeLicenseKey()} so the GroundLevel
	 * 9.1.2 {@see Credentials} writes the dynamic-plugin-id-scoped option.
	 *
	 * Writes both the per-SKU option and the stable mirror so a later id change
	 * cannot strand the key. An empty value clears both, so resets stay clean.
	 * The return value reports the per-SKU write, because that is the option the
	 * base contract describes and the value `Credentials::setLicenseKey()` hands
	 * back to its callers; the mirror is a BuddyBoss-side durability copy.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $licenseKey The license key to store.
	 * @return boolean Whether the option was updated successfully.
	 */
	public function storeLicenseKey( string $licenseKey ): bool {
		$pluginId = $this->getCurrentPluginId();
		$updated  = update_option( $pluginId . '_license_key', $licenseKey );

		if ( '' === $licenseKey ) {
			delete_option( self::STABLE_LICENSE_KEY_OPTION );

			return (bool) $updated;
		}

		update_option( self::STABLE_LICENSE_KEY_OPTION, $licenseKey );

		return (bool) $updated;
	}

	/**
	 * Option holding the licence key under a name that never changes.
	 *
	 * The per-SKU option (`{plugin_id}_license_key`) is addressed by a mutable
	 * id, so changing or clearing that id strands the key. This mirror is the
	 * durable copy; the per-SKU option is kept in step for backwards
	 * compatibility with anything reading it directly.
	 *
	 * @since BuddyBoss 3.4.3
	 *
	 * @var string
	 */
	const STABLE_LICENSE_KEY_OPTION = 'buddyboss_license_key';

	/**
	 * Gets the license key option.
	 *
	 * Convenience accessor retained for BuddyBoss callers (e.g. the add-ons manager
	 * license gate in {@see BB_Addons_Manager::render_addons_html()}). The per-SKU
	 * option and the {@see self::STABLE_LICENSE_KEY_OPTION} mirror are both read by
	 * {@see self::resolveLicenseKey()}, so this stays a thin delegate.
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
	 * existing BuddyBoss callers that expect a void return. The mirror write (and
	 * its removal on an empty key) happens there.
	 *
	 * @param string $licenseKey The license key to update.
	 */
	public function updateLicenseKey( string $licenseKey ): void {
		$this->storeLicenseKey( $licenseKey );
	}

	/**
	 * Resolves the license activation domain.
	 *
	 * Overrides {@see AbstractPluginConnection::resolveDomain()} only to pin the identifier
	 * an activation was created with; the format itself now matches the vendor's bare host.
	 *
	 * An earlier revision appended the URL path so that two installs under one host got
	 * distinct identifiers (`example.com/community`). The licensing server does not support
	 * that: the domain is sent as the Basic-auth username (percent-encoded), and while
	 * `activate` accepts a path-bearing value, the authenticated
	 * `licenses/{key}/activations/meta` lookup rejects it — so the licence screen could never
	 * load its details and the twice-daily status check had nothing to match. The vendor's own
	 * base class returns `parse_url( get_home_url(), PHP_URL_HOST )`, and so does this now.
	 *
	 * The stored-domain pin is kept, because it is what protects an existing activation: the
	 * value recorded at activation time stays authoritative even if the site URL changes
	 * later, so the record cannot be orphaned and revoked. A site activated under the old
	 * path-bearing format keeps that value until it is deactivated, at which point the next
	 * activation records a bare host.
	 *
	 * When BuddyBoss Platform is network-activated, the network home URL is used so
	 * every site in the network resolves the same domain for the shared license.
	 *
	 * A `BUDDYBOSS_DOMAIN` constant or environment variable still takes precedence via
	 * {@see \BuddyBossPlatform\GroundLevel\Mothership\Credentials::getDomain()}.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The activation domain.
	 */
	public function resolveDomain(): string {
		$stored = $this->getStoredActivationDomain();

		if ( '' !== $stored ) {
			return $stored;
		}

		$home_url = $this->is_network_activated() ? network_home_url() : get_home_url();

		return (string) wp_parse_url( $home_url, PHP_URL_HOST );
	}

	/**
	 * Option holding the domain a license was actually activated against.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const ACTIVATION_DOMAIN_OPTION = 'buddyboss_license_activation_domain';

	/**
	 * Gets the domain stored at activation time.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The stored domain, or an empty string when none is stored.
	 */
	public function getStoredActivationDomain(): string {
		$stored = get_option( self::ACTIVATION_DOMAIN_OPTION, '' );

		return is_string( $stored ) ? $stored : '';
	}

	/**
	 * Stores the domain a license was activated against.
	 *
	 * Called on a successful activation so the identifier stays stable for the life of
	 * that activation, whatever the site URL does afterwards.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $domain The domain the activation was performed with.
	 */
	public function storeActivationDomain( string $domain ): void {
		if ( '' === $domain ) {
			delete_option( self::ACTIVATION_DOMAIN_OPTION );

			return;
		}

		update_option( self::ACTIVATION_DOMAIN_OPTION, $domain );
	}

	/**
	 * Clears the stored activation domain.
	 *
	 * Called on deactivation/reset so the next activation resolves a fresh identifier.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function clearActivationDomain(): void {
		delete_option( self::ACTIVATION_DOMAIN_OPTION );
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
