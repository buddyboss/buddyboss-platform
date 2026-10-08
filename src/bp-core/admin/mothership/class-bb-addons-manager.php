<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonInstallSkin;
use BuddyBossPlatform\GroundLevel\Mothership\ExtensionType;
use BuddyBossPlatform\GroundLevel\Mothership\Util;

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
	 * @since BuddyBoss 3.5.1
	 *
	 * @return \BuddyBossPlatform\GroundLevel\Container\Container
	 */
	private static function container() {
		return BB_Mothership_Loader::instance()->get_container();
	}

	/**
	 * Resolve the vendor add-ons manager instance from the container.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * Returns null when the container has no such service — which happens when the
	 * GroundLevel vendor tree is stale (so {@see BB_Mothership_Loader::init()} bailed
	 * before registering anything) or when provider boot threw. Callers must treat null
	 * as "the add-ons API is unavailable", never as "the plan has no add-ons".
	 *
	 * @since BuddyBoss 3.5.1
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
	 * @since BuddyBoss 3.5.1
	 *
	 * Returns null when the container has no such service — see
	 * {@see self::addons_manager()} for when that happens.
	 *
	 * @since BuddyBoss 3.5.1
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
			return '<div class="notice notice-error"><p>' . esc_html__( 'Add-ons are unavailable because the licensing library failed to load. Please contact support.', 'buddyboss-platform' ) . '</p></div>';
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
			return '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Please activate your license to access add-ons.', 'buddyboss-platform' ) . '</p></div>';
		}

		if ( ! $plugin->getLicenseKey() ) {
			return '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter your license key to access add-ons.', 'buddyboss-platform' ) . '</p></div>';
		}

		$addons_manager = self::addons_manager();

		if ( null === $addons_manager ) {
			return '<div class="notice notice-error"><p>' . esc_html__( 'Add-ons are unavailable because the licensing library failed to load. Please contact support.', 'buddyboss-platform' ) . '</p></div>';
		}

		// Refresh the add-ons if the button is clicked (nonce verified above).
		if ( $refresh_requested ) {
			$addons_manager->clearCache();

			// A forced refresh is a real fetch, not an outage re-seed, and it replaces the
			// outage copy: dropping it lets the next tracked read store the fresh list.
			delete_transient( $plugin->pluginId . self::RESEEDED_ADDONS_SUFFIX ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			delete_transient( $plugin->pluginId . self::LAST_GOOD_ADDONS_SUFFIX ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// The vendor drops its own transient; the memo has to go with it.
			self::reset_addons_memo();
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
	 * Gets the latest release of an add-on product returned by {@see self::checkProductBySlug()}.
	 *
	 * GroundLevel 9.1.2 moves the embedded latest release onto `$product->version` and unsets
	 * `$product->_embedded->{'version-latest'}`, so readers written against the 2.2.1 shape
	 * always saw an empty release. The legacy location is still read as a fallback for any
	 * product object that did not come through the 9.1.2 add-ons manager.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param object|null $product Add-on product object.
	 * @return object|null The release object (exposing `number` and `url`), or null when none.
	 */
	public static function get_product_latest_version( $product ): ?object {
		if ( ! is_object( $product ) ) {
			return null;
		}

		if ( isset( $product->version ) && is_object( $product->version ) ) {
			return $product->version;
		}

		if ( isset( $product->_embedded->{'version-latest'} ) && is_object( $product->_embedded->{'version-latest'} ) ) {
			return $product->_embedded->{'version-latest'};
		}

		return null;
	}

	/**
	 * Gets the package download URL of an add-on's latest release.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param object|null $product Add-on product object.
	 * @return string The download URL, or an empty string when none is available.
	 */
	public static function get_product_download_url( $product ): string {
		$version = self::get_product_latest_version( $product );

		return ( null !== $version && ! empty( $version->url ) ) ? (string) $version->url : '';
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
	 * @since BuddyBoss 3.5.1
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
	 * @since BuddyBoss 3.5.1
	 *
	 * @var string
	 */
	const PRODUCTS_ERROR_TRANSIENT = 'bb_products_api_error';

	/**
	 * Suffix of the transient holding the last non-empty add-ons list for a plugin ID.
	 *
	 * GroundLevel 9.1.2 caches the list for only 60 minutes and, when a fetch fails on an
	 * expired cache, stores an empty list. This BuddyBoss-owned copy lets an outage keep
	 * serving the customer's real plan instead of turning every add-on into an upsell.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var string
	 */
	const LAST_GOOD_ADDONS_SUFFIX = '_bb_addons_last_good';

	/**
	 * How long the last non-empty add-ons list is kept, in seconds.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var int
	 */
	const LAST_GOOD_ADDONS_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Suffix of the transient marking the vendor add-ons cache as re-seeded from the
	 * last-good copy (rather than filled by a real fetch).
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var string
	 */
	const RESEEDED_ADDONS_SUFFIX = '_bb_addons_reseeded';

	/**
	 * Per-request memo of the add-ons list, or null when not yet resolved.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var array|null
	 */
	private static $addons_memo = null;

	/**
	 * Forgets the memoised add-ons list.
	 *
	 * Must be called by anything that invalidates the underlying add-ons cache, otherwise
	 * a clear performed mid-request would be invisible to later reads in the same request.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	public static function reset_addons_memo(): void {
		self::$addons_memo = null;
	}

	/**
	 * Fetches the add-ons list, recording an outage when the fetch fails on a cold cache.
	 *
	 * {@see AddonsManager::getAddons()} swallows transport failures: it caches an EMPTY
	 * array for `ERROR_TTL_MINUTES` and returns it, indistinguishable from "your plan has
	 * no add-ons". Every outage guard downstream therefore fails open in the wrong
	 * direction — a licensed customer gets UPGRADE placeholder cards and DRM nags.
	 *
	 * Two signals tell the two apart:
	 *
	 * - A BuddyBoss-owned copy of the last non-empty list exists, but the vendor now returns
	 *   nothing. The vendor's own update filter often fetches first on an admin page load
	 *   (and caches the empty list), so the cold-cache probe alone misses most outages. The
	 *   copy is served instead, and re-seeded into the vendor cache for the vendor's error
	 *   TTL, so every consumer sees the real plan during the outage. The copy is dropped on
	 *   any license change, so it never outlives the license it was fetched for.
	 * - Otherwise, the vendor cache was absent BEFORE the call and the call returned nothing.
	 *   A licensed plan that genuinely returns nothing is also recorded, but that errs toward
	 *   suppressing an upsell — the safe direction.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @return array The add-ons list (possibly empty).
	 */
	protected static function get_addons_tracked(): array {
		// Memoised per request. checkProductBySlug() is called once per add-on by the DRM
		// sweep and again per slug by the placeholder-card loop, and each call otherwise
		// re-read the add-ons transient twice (once for the cold-cache probe below, once
		// inside the vendor's getAddons()). Measured at 12 transient reads for 6 slugs.
		// Invalidated by self::reset_addons_memo(), which every cache-clearing path calls.
		if ( null !== self::$addons_memo ) {
			return self::$addons_memo;
		}

		$connection = self::plugin_connection();
		$manager    = self::addons_manager();

		if ( null === $connection || null === $manager ) {
			// Not memoised: the container may still come up later in the request.
			return array();
		}

		$cache_key = $connection->pluginId . self::CACHE_KEY_ADDONS; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$was_cold  = ( false === get_transient( $cache_key ) );

		$addons = $manager->getAddons( true );

		if ( ! is_array( $addons ) ) {
			$addons = array();
		}

		$last_good_key = $connection->pluginId . self::LAST_GOOD_ADDONS_SUFFIX; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$reseeded_key  = $connection->pluginId . self::RESEEDED_ADDONS_SUFFIX; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( ! empty( $addons ) ) {
			// A list re-seeded from the last-good copy is not a fresh fetch: it must neither
			// lift the outage marker nor extend the copy's 12h deadline.
			if ( false === get_transient( $reseeded_key ) ) {
				delete_transient( self::PRODUCTS_ERROR_TRANSIENT );

				// Written only after a real fetch (or to seed a missing copy), so warm reads
				// cause no database write.
				if ( $was_cold || false === get_transient( $last_good_key ) ) {
					set_transient( $last_good_key, $addons, self::LAST_GOOD_ADDONS_TTL );
				}
			}
		} else {
			$last_good = get_transient( $last_good_key );

			if ( is_array( $last_good ) && ! empty( $last_good ) ) {
				// Serve the real plan through the outage; retried after the vendor error TTL.
				$addons = $last_good;
				set_transient( $cache_key, $addons, self::ERROR_TTL_MINUTES * MINUTE_IN_SECONDS );
				set_transient( $reseeded_key, 1, self::ERROR_TTL_MINUTES * MINUTE_IN_SECONDS );
				set_transient( self::PRODUCTS_ERROR_TRANSIENT, 1, self::ERROR_TTL_MINUTES * MINUTE_IN_SECONDS );
			} elseif ( $was_cold ) {
				// Match the vendor's own error TTL so the two expire together.
				set_transient( self::PRODUCTS_ERROR_TRANSIENT, 1, self::ERROR_TTL_MINUTES * MINUTE_IN_SECONDS );
			}
		}

		self::$addons_memo = $addons;

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
		self::reset_addons_memo();

		$connection = self::plugin_connection();

		if ( null === $connection ) {
			// Still lift our own outage marker even when the container is unavailable.
			delete_transient( self::PRODUCTS_ERROR_TRANSIENT );

			return;
		}

		$plugin_id = $connection->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		delete_transient( $plugin_id . self::CACHE_KEY_ADDONS );
		delete_site_transient( $plugin_id . self::CACHE_KEY_ADDONS );

		// The last-known-good copy belongs to the license it was fetched for.
		delete_transient( $plugin_id . self::LAST_GOOD_ADDONS_SUFFIX );
		delete_transient( $plugin_id . self::RESEEDED_ADDONS_SUFFIX );

		// A manual refresh / license change must lift the recorded outage too, otherwise
		// the upsell guards stay suppressed for the rest of the error window.
		delete_transient( self::PRODUCTS_ERROR_TRANSIENT );
	}

	/**
	 * Map the base add-on slug BuddyBoss posts to the product's real slug before the vendor
	 * add-on AJAX handlers run.
	 *
	 * BuddyBoss callers (Email Digest card, placeholder feature cards, Settings screen) post
	 * the base slug, e.g. `buddyboss-addons`, and entitlement is decided with
	 * {@see self::checkProductBySlug()}, which matches by prefix. The licensing server can
	 * list the product under a plan-specific slug (e.g. `buddyboss-addons-scale`), and
	 * GroundLevel's {@see AddonsManager::getAddon()} matches exactly, so the vendor
	 * install/activate/deactivate handlers answered "Add-on not found" for an add-on the
	 * card had just offered.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param string $plugin_id The dynamic plugin ID the vendor names the AJAX actions after.
	 */
	public static function register_ajax_slug_normalizer( string $plugin_id ): void {
		foreach ( array( 'activate', 'deactivate', 'install' ) as $action ) {
			add_action( "wp_ajax_{$plugin_id}_addon_{$action}", array( self::class, 'normalize_ajax_addon_slug' ), 1 );
		}
	}

	/**
	 * Rewrites `$_POST['slug']` to the entitled product's real slug when it has no exact match.
	 *
	 * Runs before the vendor handler (and the network handlers), which verify the nonce and
	 * capabilities themselves; this only changes which product they look up.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	public static function normalize_ajax_addon_slug(): void {
		if ( ! check_ajax_referer( 'mosh_addons', false, false ) || empty( $_POST['slug'] ) || ! is_string( $_POST['slug'] ) ) {
			return;
		}

		$slug    = sanitize_text_field( wp_unslash( $_POST['slug'] ) );
		$manager = self::addons_manager();

		if ( '' === $slug || null === $manager || null !== $manager->getAddon( $slug ) ) {
			return;
		}

		$product = self::checkProductBySlug( $slug );

		if ( null !== $product && ! empty( $product->slug ) && is_string( $product->slug ) ) {
			$_POST['slug'] = $product->slug;
		}
	}

	/**
	 * Route add-on activate/deactivate/install requests to network-wide handlers.
	 *
	 * When Platform is network-activated the add-ons page lives in Network Admin, but the
	 * vendor AJAX handlers call `activate_plugin()` / `deactivate_plugins()` without the
	 * network flag, so an add-on "activated" there only ran on the main site. These handlers
	 * run first (priority 5, the vendor's run at 10) and exit with the same JSON shape the
	 * vendor `addons.js` expects. Theme add-ons fall through to the vendor, as themes are
	 * switched per site.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param string $plugin_id The dynamic plugin ID the vendor names the AJAX actions after.
	 */
	public static function register_network_ajax_handlers( string $plugin_id ): void {
		if ( ! BB_Plugin_Connector::is_network_mode() ) {
			return;
		}

		foreach ( array( 'activate', 'deactivate', 'install' ) as $action ) {
			add_action(
				"wp_ajax_{$plugin_id}_addon_{$action}",
				static function () use ( $action ) {
					self::container()->get( self::class )->network_ajax_handler( $action );
				},
				5
			);
		}
	}

	/**
	 * Activate, deactivate or install a plugin add-on network-wide.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param string $action One of `activate`, `deactivate`, `install`.
	 */
	public function network_ajax_handler( string $action ): void {
		// Validates the nonce and loads the requested add-on into $this->ajaxProduct.
		$this->setupAjaxRequest(); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

		$product   = $this->ajaxProduct; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$main_file = $product->main_file ?? '';

		if ( ExtensionType::PLUGIN !== ( $product->extension_type ?? '' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_network_plugins' ) || ( 'install' === $action && ! current_user_can( 'install_plugins' ) ) ) {
			wp_send_json_error( new \WP_Error( 'insufficient_permissions', esc_html__( 'Sorry, you do not have permission to manage network add-ons.', 'buddyboss-platform' ) ) );
		}

		if ( 'activate' === $action ) {
			$result = $main_file ? activate_plugin( $main_file, '', true ) : false;

			if ( null !== $result ) {
				wp_send_json_error( new \WP_Error( 'activation_failed', esc_html__( 'The add-on could not be network activated.', 'buddyboss-platform' ) ) );
			}

			wp_send_json_success( esc_html__( 'Plugin network activated.', 'buddyboss-platform' ) );
		}

		if ( 'deactivate' === $action ) {
			if ( ! $main_file ) {
				wp_send_json_error( new \WP_Error( 'deactivation_failed', esc_html__( 'The add-on could not be deactivated.', 'buddyboss-platform' ) ) );
			}

			deactivate_plugins( $main_file, false, true );
			wp_send_json_success( esc_html__( 'Plugin network deactivated.', 'buddyboss-platform' ) );
		}

		$this->network_install_addon( $product );
	}

	/**
	 * Install a plugin add-on and network-activate it.
	 *
	 * Mirrors {@see AddonsManager::ajaxAddonInstall()} for plugins, differing only in the
	 * network-wide activation.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param object $product The add-on product from the add-ons API.
	 */
	private function network_install_addon( $product ): void {
		set_current_screen();
		$creds = request_filesystem_credentials( network_admin_url( 'admin.php' ), '', false, false, null );
		if ( false === $creds || ! \WP_Filesystem( $creds ) ) {
			wp_send_json_error( new \WP_Error( 'insufficient_permissions', esc_html__( 'Sorry, you do not have permission to install add-ons.', 'buddyboss-platform' ) ) );
		}

		$addon_url = $product->version->url ?? '';
		if ( ! self::container()->get( Util::class )->isAllowedDownloadUrl( $addon_url ) ) {
			wp_send_json_error( new \WP_Error( 'invalid_addon_url', esc_html__( 'Invalid add-on URL.', 'buddyboss-platform' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		$installer = new \Plugin_Upgrader( new AddonInstallSkin() );
		$installed = $installer->install( $addon_url );
		if ( ! $installed || is_wp_error( $installed ) ) {
			wp_send_json_error( new \WP_Error( 'addon_install_failed', esc_html__( 'The add-on was not installed successfully.', 'buddyboss-platform' ) ) );
		}

		wp_cache_flush();

		$base_name = $installer->plugin_info();
		$activated = $base_name && null === activate_plugin( $base_name, '', true );

		wp_send_json_success(
			array(
				'message'   => $activated ? esc_html__( 'Plugin installed and network activated.', 'buddyboss-platform' ) : esc_html__( 'Plugin installed.', 'buddyboss-platform' ),
				'activated' => $activated,
			)
		);
	}

	/**
	 * Prepare add-ons for display, reporting network activation in network mode.
	 *
	 * The vendor derives "Active" from `is_plugin_active()`, which is also true for a plugin
	 * active on the main site only. On the Network Admin add-ons page that hid the Activate
	 * button for add-ons that were not running network-wide, so it is re-evaluated here.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param array $products The products to prepare.
	 * @return array The prepared products.
	 */
	protected function prepareProductsForDisplay( array $products ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$products = parent::prepareProductsForDisplay( $products );

		if ( ! BB_Plugin_Connector::is_network_mode() ) {
			return $products;
		}

		foreach ( $products as $product ) {
			if (
				'active' === $product->status &&
				ExtensionType::PLUGIN === ( $product->extension_type ?? '' ) &&
				! is_plugin_active_for_network( $product->main_file )
			) {
				$product->status      = 'inactive';
				$product->statusLabel = esc_html__( 'Inactive', 'buddyboss-platform' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$product->iconClass   = 'dashicons dashicons-yes-alt'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$product->buttonLabel = esc_html__( 'Activate', 'buddyboss-platform' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		return $products;
	}
}
