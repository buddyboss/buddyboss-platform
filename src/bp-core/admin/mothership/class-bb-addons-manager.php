<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager;

/**
 * BuddyBoss add-ons manager (static facade over the GroundLevel AddonsManager).
 *
 * GroundLevel 7.4.0 turned {@see AddonsManager} into an instance service with a five-argument
 * constructor, resolved from the container via auto-wiring. BuddyBoss calls its add-ons manager
 * statically throughout the codebase (admin page, DRM add-on gating, placeholder cards, and the
 * plugin connector's cache invalidation), so this class keeps a static facade and delegates to
 * the container-managed vendor instance.
 *
 * GroundLevel 9.x changed the data shape: {@see AddonsManager::getAddons()} returns a plain
 * array of add-on objects (fetched as relations of the connected product, so the connector's
 * `productId` must be set), and the list also contains products typed `upgrade-addon` — add-ons
 * that exist for the product line but are NOT included in the current license. Those are shown
 * as "Upgrade" cards on the add-ons page and must never count as licensed.
 *
 * It also `extends AddonsManager` so it can reuse the vendor's `protected prepareProductsForDisplay()`
 * via a container-resolved instance of itself (see {@see self::render_addons_html()}) instead of
 * duplicating that logic. Because the static facade cannot redeclare the vendor's instance method
 * of the same name, BuddyBoss's renderer is named `render_addons_html()` rather than
 * `generateAddonsHtml()`. The cache key suffix is inherited from {@see AddonsManager::CACHE_KEY_ADDONS}.
 *
 * @since BuddyBoss 2.14.0
 */
class BB_Addons_Manager extends AddonsManager {

	/**
	 * Get the BuddyBoss Mothership container.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return \BuddyBossPlatform\GroundLevel\Container\Container
	 */
	private static function container() {
		return BB_Mothership_Loader::instance()->get_container();
	}

	/**
	 * Resolve the vendor add-ons manager instance from the container.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * Returns null when the container has no such service — which happens when the
	 * GroundLevel vendor tree is stale (so {@see BB_Mothership_Loader::init()} bailed
	 * before registering anything) or when provider boot threw. Callers must treat null
	 * as "the add-ons API is unavailable", never as "the plan has no add-ons".
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return AddonsManager|null
	 */
	private static function addons_manager(): ?AddonsManager {
		try {
			return self::container()->get( AddonsManager::class );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Resolve the plugin connection from the container.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * Returns null when the container has no such service — see
	 * {@see self::addons_manager()} for when that happens.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return AbstractPluginConnection|null
	 */
	private static function plugin_connection(): ?AbstractPluginConnection {
		try {
			return self::container()->get( AbstractPluginConnection::class );
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Generates and returns the HTML for the add-ons using BuddyBoss's local view.
	 *
	 * @return string The HTML for the add-ons.
	 */
	public static function render_addons_html(): string {
		$plugin = self::plugin_connection();

		// Mothership never booted (stale vendor / failed provider boot) — say so instead of
		// fataling the add-ons screen.
		if ( null === $plugin ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Add-ons are unavailable because the licensing library failed to load. Please contact support.', 'buddyboss' ) . '</p></div>';
		}

		// "Refresh Add-ons" must also re-validate the license, not just drop the add-ons
		// cache. Nothing else re-checks on demand: the only thing that notices a license
		// revoked or expired server-side is GroundLevel's TWICE-DAILY cron, so until it ran
		// this screen kept reporting a dead license as connected while the API answered 401
		// and the add-ons list came back empty. Done before the status gate below so the
		// revocation is reflected on this very request.
		$refresh_requested = (
			isset( $_POST['submit-button-mosh-refresh-addon'], $_POST['bb_mosh_refresh_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bb_mosh_refresh_nonce'] ) ), 'bb_mosh_refresh_addons' )
		);

		if ( $refresh_requested ) {
			self::refresh_license_status();
		}

		// Check if license is activated before making API calls.
		if ( ! $plugin->getLicenseActivationStatus() ) {
			return '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Please activate your license to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		if ( ! $plugin->getLicenseKey() ) {
			return '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter your license key to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		$addons_manager = self::addons_manager();

		if ( null === $addons_manager ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Add-ons are unavailable because the licensing library failed to load. Please contact support.', 'buddyboss' ) . '</p></div>';
		}

		// Refresh the add-ons if the button is clicked (nonce verified above).
		if ( $refresh_requested ) {
			$addons_manager->clearCache();
		}

		// getAddons() returns the cached add-on list; on an API error the vendor keeps the
		// last successful list (or an empty list) for a short TTL, so there is no error
		// response to surface here — an empty list renders the "no add-ons" message.
		$addons_manager->enqueueAssets();

		// Reuse the vendor's display-prep logic. It is protected on AddonsManager, so we call
		// it on a container-resolved instance of this subclass (legal from within the class).
		$products = self::container()->get( self::class )->prepareProductsForDisplay( // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			$addons_manager->getAddons( true )
		);
		ob_start();
		include __DIR__ . '/views/products.php';
		return ob_get_clean();
	}

	/**
	 * Check if a product exists and is enabled by slug.
	 *
	 * Reads from the vendor add-ons cache ({@see AddonsManager::getAddons()}); no separate
	 * BuddyBoss cache layer is maintained. Products typed `upgrade-addon` are add-ons the
	 * current license does NOT include, so they are skipped — this method gates DRM and the
	 * placeholder feature cards, and must only ever return licensed add-ons.
	 *
	 * @param string $slug Product slug to check.
	 * @return object|null Product object if found, licensed and enabled, null otherwise.
	 */
	public static function checkProductBySlug( string $slug ): ?object {
		// Check if the license is activated before making API calls.
		$connection = self::plugin_connection();
		if ( null === $connection || ! $connection->getLicenseActivationStatus() ) {
			return null;
		}

		foreach ( self::get_addons_tracked() as $product ) {
			if ( ! is_object( $product ) || 'upgrade-addon' === ( $product->type ?? '' ) ) {
				continue;
			}

			if (
				! empty( $product->slug ) &&
				false !== strpos( $product->slug, $slug ) &&
				! empty( $product->status ) &&
				'enabled' === $product->status
			) {
				return $product;
			}
		}

		return null;
	}

	/**
	 * Whether the last add-ons API lookup could not be trusted.
	 *
	 * Lets callers tell "this product is not in your plan" apart from "we could not
	 * reach the add-ons API", which look identical through {@see self::checkProductBySlug()}.
	 *
	 * GroundLevel 9.1.2 narrowed what this can report. {@see AddonsManager::getAddons()}
	 * returns a plain array, not an API response, and the vendor caches its own failures
	 * (`ERROR_TTL_MINUTES`) and serves the last successful list through an outage — so
	 * there is no error object left to inspect, and the 2.2.1-era products error transient
	 * this used to read no longer exists. What stays observable is an exhausted rate-limit
	 * quota recorded from the licensing API's `Retry-After` / `X-RateLimit-Reset` headers,
	 * which is the outage shape these guards were added for. An ordinary transport failure
	 * on a cold cache now reads as an empty plan rather than an error, because the vendor
	 * absorbs it; callers that must fail closed already do so on an empty result.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @return bool True when the add-ons list could not be retrieved.
	 */
	public static function productsApiErrored(): bool { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$connection = self::plugin_connection();

		// No container service means Mothership never booted (stale vendor, failed provider
		// boot). That is an outage, not a clean "no error" — reporting false here would let
		// the upsell/DRM guards treat a licensed customer as unlicensed.
		if ( null === $connection ) {
			return true;
		}

		if ( ! $connection->getLicenseActivationStatus() ) {
			// Without an active license there is no API call to fail.
			return false;
		}

		return self::rate_limit_seconds_remaining() > 0 || false !== get_transient( self::PRODUCTS_ERROR_TRANSIENT );
	}

	/**
	 * Re-validates the stored license against the licensing server.
	 *
	 * Delegates to the vendor's own {@see LicenseManager::checkLicenseActivationStatus()},
	 * which retrieves the activation and, on a 401/403/404, fires
	 * `{pluginId}_active_license_invalidated` / `_active_license_expired`. BuddyBoss already
	 * listens for both in {@see BB_Mothership_Loader::setup_hooks()} and flips the local
	 * activation status, so reusing the vendor path keeps one definition of "revoked"
	 * instead of reimplementing it here. The license KEY is left in place, so the admin can
	 * simply re-activate.
	 *
	 * Also drops our outage marker, so a license that has come back to life is not masked
	 * by a stale "products API errored" flag.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	protected static function refresh_license_status(): void {
		delete_transient( self::PRODUCTS_ERROR_TRANSIENT );

		try {
			self::container()->get( LicenseManager::class )->checkLicenseActivationStatus();
		} catch ( \Throwable $e ) {
			// Container unavailable (stale vendor / failed boot) — the add-ons refresh below
			// still runs; there is simply nothing to re-validate against.
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( sprintf( 'BuddyBoss: license status refresh failed: %s', $e->getMessage() ), true );
			}
		}
	}

	/**
	 * Transient recording that an add-ons fetch failed on a cold cache.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const PRODUCTS_ERROR_TRANSIENT = 'bb_products_api_error';

	/**
	 * Fetches the add-ons list, recording an outage when the fetch fails on a cold cache.
	 *
	 * {@see AddonsManager::getAddons()} swallows transport failures: it caches an EMPTY
	 * array for `ERROR_TTL_MINUTES` and returns it, indistinguishable from "your plan has
	 * no add-ons". Every outage guard downstream therefore fails open in the wrong
	 * direction — a licensed customer gets UPGRADE placeholder cards and DRM nags.
	 *
	 * A warm cache tells the two apart: if the vendor cache was absent BEFORE the call and
	 * the call returned nothing, the fetch failed. A licensed plan that genuinely returns
	 * nothing is also recorded, but that errs toward suppressing an upsell — the safe
	 * direction.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array The add-ons list (possibly empty).
	 */
	protected static function get_addons_tracked(): array {
		$connection = self::plugin_connection();
		$manager    = self::addons_manager();

		if ( null === $connection || null === $manager ) {
			return array();
		}

		$cache_key = $connection->pluginId . self::CACHE_KEY_ADDONS; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$was_cold  = ( false === get_transient( $cache_key ) );

		$addons = $manager->getAddons( true );

		if ( ! is_array( $addons ) ) {
			$addons = array();
		}

		if ( ! empty( $addons ) ) {
			delete_transient( self::PRODUCTS_ERROR_TRANSIENT );
		} elseif ( $was_cold ) {
			// Match the vendor's own error TTL so the two expire together.
			set_transient( self::PRODUCTS_ERROR_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );
		}

		return $addons;
	}

	/**
	 * Seconds until the recorded licensing API rate-limit window resets.
	 *
	 * Reads the `bb_license_rate_limit` transient written by
	 * {@see BB_License_Manager::capture_api_headers()}. Only an exhausted quota
	 * (`remaining` of zero with a future reset) counts as blocking; rate-limit
	 * headers on healthy responses do not pause anything.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @return int Seconds remaining in the block, or 0 when not rate limited.
	 */
	protected static function rate_limit_seconds_remaining(): int {
		$data = get_transient( 'bb_license_rate_limit' );

		if ( ( empty( $data ) || ! is_array( $data ) ) && is_multisite() ) {
			$data = get_site_transient( 'bb_license_rate_limit' );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return 0;
		}

		$reset     = isset( $data['reset'] ) ? (int) $data['reset'] : 0;
		$remaining = isset( $data['remaining'] ) ? $data['remaining'] : null;

		if ( null === $remaining || (int) $remaining > 0 ) {
			return 0;
		}

		$seconds = $reset - time();

		/*
		 * A reset more than a day out is corrupt data (e.g. a millisecond epoch in
		 * X-RateLimit-Reset). Ignore it rather than let a bogus timestamp report an
		 * outage forever.
		 */
		if ( $seconds > DAY_IN_SECONDS ) {
			return 0;
		}

		return max( 0, $seconds );
	}

	/**
	 * Clear the add-ons cache.
	 *
	 * Invalidates the vendor add-ons cache (`{pluginId}-mosh-addons`) so the live add-ons list
	 * refreshes after a license status change, a dynamic-plugin-ID change, or a manual refresh.
	 * Every license state-change routes through here via the plugin connector. The transient is
	 * deleted by key (rather than through the container) so it stays safe even if the service
	 * container failed to boot. The legacy 2.2.1 keys (`-mosh-products` /
	 * `-mosh-addons-update-check`) no longer exist in 7.4.0.
	 *
	 * @since BuddyBoss 2.14.0
	 */
	public static function clearProductAddOnsCache(): void {
		$connection = self::plugin_connection();

		if ( null === $connection ) {
			// Still lift our own outage marker even when the container is unavailable.
			delete_transient( self::PRODUCTS_ERROR_TRANSIENT );

			return;
		}

		$plugin_id = $connection->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		delete_transient( $plugin_id . self::CACHE_KEY_ADDONS );
		delete_site_transient( $plugin_id . self::CACHE_KEY_ADDONS );

		// A manual refresh / license change must lift the recorded outage too, otherwise
		// the upsell guards stay suppressed for the rest of the error window.
		delete_transient( self::PRODUCTS_ERROR_TRANSIENT );
	}
}
