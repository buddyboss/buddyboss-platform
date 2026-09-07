<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Custom AddonsManager for BuddyBoss that extends the vendor's AddonsManager.
 * This class overrides the view loading to use our local view files.
 */
class BB_Addons_Manager extends AddonsManager {

	/**
	 * How long a successful add-ons lookup stays cached, in seconds.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @var int
	 */
	protected const CACHE_DURATION_ADD_ONS = 12 * HOUR_IN_SECONDS;

	/**
	 * How long a failed add-ons lookup stays cached, in seconds.
	 *
	 * Deliberately short. Long enough to stop a loop of per-item lookups from firing
	 * one failing HTTP request each, short enough that a transient network problem
	 * does not keep reading as "this product is not in your plan".
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @var int
	 */
	protected const CACHE_DURATION_ADD_ONS_ERROR = 2 * MINUTE_IN_SECONDS;

	/**
	 * Transient key suffix for the products API error backoff cache.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @var string
	 */
	protected const CACHE_KEY_PRODUCTS_ERROR = '-mosh-products-error';

	/**
	 * How long a failed products API lookup blocks further live calls, in seconds.
	 *
	 * Mirrors the ERROR_TTL_MINUTES = 5 error cache that ground-level-mothership 8.x
	 * applies in AddonsManager::getAddons(). Without it, every read of the
	 * `update_plugins` / `update_themes` site transients retries the failed request.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @var int
	 */
	protected const CACHE_DURATION_PRODUCTS_ERROR = 5 * MINUTE_IN_SECONDS;

	/**
	 * Upper bound for a single products error backoff window, in seconds.
	 *
	 * A legitimate server-provided rate-limit reset longer than this re-arms in
	 * one-hour windows until the reset passes; implausible resets (more than a day
	 * out) are discarded as corrupt by `bb_rate_limit_seconds_remaining()`.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @var int
	 */
	protected const CACHE_DURATION_PRODUCTS_ERROR_MAX = HOUR_IN_SECONDS;

	/**
	 * The unwrapped products API client captured before the backoff wrapper is installed.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @var callable|null
	 */
	protected static $live_products_api_client = null;

	/**
	 * Register add-on hooks and install the products API backoff wrapper.
	 *
	 * @since BuddyBoss 3.3.0
	 * @since BuddyBoss 3.4.4 Routes the shared `AddonsManager::$productsApiClient`
	 *                              through the error backoff wrapper.
	 */
	public static function loadHooks(): void {
		parent::loadHooks();

		$cache_products_key = self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_PRODUCTS;
		$cache_update_key   = self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_UPDATE_CHECK;

		add_filter( 'pre_set_transient_' . $cache_update_key, [ self::class, 'pre_set_addon_update_transient' ], 10, 1 );
		add_action( 'delete_transient_' . $cache_products_key, [ self::class, 'clearProductAddOnsCache' ] );

		/*
		 * Route every products-list API call through the error backoff wrapper.
		 *
		 * The static property is shared with the vendor AddonsManager class, so this
		 * also covers the vendor-registered `site_transient_update_plugins` /
		 * `site_transient_update_themes` filters and add-on AJAX handlers, all of
		 * which call the vendor `getAddons()` directly.
		 */
		if ( ! is_array( self::$productsApiClient ) || self::class !== ( self::$productsApiClient[0] ?? null ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- vendor property.
			self::$live_products_api_client = self::$productsApiClient; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- vendor property.
			self::$productsApiClient        = array( self::class, 'bb_products_list_with_backoff' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- vendor property.
		}
	}

	/**
	 * Products API client wrapper that debounces failed lookups.
	 *
	 * The bundled ground-level-mothership 2.2.1 only caches successful products
	 * responses: `getAddons()` stores nothing on an error/429, so the next read of the
	 * `update_plugins` / `update_themes` site transients immediately fires another
	 * live `GET /products?_embed=version-latest`. On a busy wp-admin that becomes a
	 * sustained retry loop against licenses.caseproof.com.
	 *
	 * Installed as `AddonsManager::$productsApiClient`, so every `getAddons()`
	 * caller shares one backoff:
	 *
	 * - While a previous failure is cached, return it without an HTTP request.
	 * - While a rate-limit block recorded from `Retry-After` / `X-RateLimit-Reset`
	 *   headers is active, fail fast without an HTTP request.
	 * - After a live failure, cache the error response for at least five minutes,
	 *   or until the server-provided rate limit reset (capped at one hour).
	 * - When a failure happens while the last successful catalog is still cached,
	 *   serve that stale catalog instead of the error (the 8.x/9.x behavior), so
	 *   update checks keep working through short API blips.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @param array $args Query args for the products list request.
	 * @return Response The live or cached products API response.
	 */
	public static function bb_products_list_with_backoff( array $args = array() ) {
		$error_key = self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_PRODUCTS_ERROR;

		$cached_error = get_transient( $error_key );
		if ( $cached_error instanceof Response ) {
			// Same stale fallback as the live-failure path below, so readers that
			// land inside an open backoff window (or while another request is in
			// flight) also keep the last good catalog instead of the raw error.
			$stale = self::bb_stale_products_response();

			return null !== $stale ? $stale : $cached_error;
		}

		// A rate-limit block recorded by any licensing API call also gates this path.
		$retry_delay = self::bb_rate_limit_seconds_remaining();
		if ( $retry_delay > 0 ) {
			$response = new Response(
				null,
				__( 'Rate limit exceeded. Add-on update checks are paused until the rate limit resets.', 'buddyboss' ),
				429,
				array()
			);
			set_transient( $error_key, $response, self::bb_products_error_backoff_duration( $retry_delay ) );

			$stale = self::bb_stale_products_response();

			return null !== $stale ? $stale : $response;
		}

		/*
		 * Claim the retry before calling live: concurrent requests that arrive while
		 * this request is still waiting on the API are served this placeholder error
		 * instead of firing their own live call (no burst at the window boundary).
		 * On success it is deleted below; on failure it is overwritten with the real
		 * error; if the process dies mid-request it expires on its own.
		 */
		set_transient(
			$error_key,
			new Response( null, __( 'An add-ons API request is already in progress.', 'buddyboss' ) ),
			2 * MINUTE_IN_SECONDS
		);

		$response = self::bb_request_products_list( $args );

		if ( self::isErrorResponse( $response ) ) {
			if ( ! $response instanceof Response ) {
				$response = new Response( null, __( 'The add-ons API request did not complete.', 'buddyboss' ) );
			}

			/*
			 * Cache the failure so the next transient read serves it from here instead
			 * of re-calling the API. `bb_rate_limit_seconds_remaining()` reflects any
			 * Retry-After / X-RateLimit-Reset headers captured during this request.
			 */
			set_transient( $error_key, $response, self::bb_products_error_backoff_duration( self::bb_rate_limit_seconds_remaining() ) );

			/*
			 * Serve the last successful products list while the API is failing, like
			 * ground-level-mothership 8.x does ("on error the last cached list is
			 * kept"). Update checks keep working through short API blips, and the
			 * caller re-marks the cache fresh, which debounces the next live retry.
			 */
			$stale = self::bb_stale_products_response();
			if ( null !== $stale ) {
				return $stale;
			}
		} else {
			delete_transient( $error_key );
		}

		return $response;
	}

	/**
	 * Last successful products API response still held in the products cache.
	 *
	 * The success cache (`-mosh-products`, 60 minutes) usually outlives the
	 * update-check freshness marker (30 minutes), so a stale-but-valid catalog is
	 * often available when a refresh attempt fails.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @return Response|null The cached successful response, or null when absent.
	 */
	protected static function bb_stale_products_response(): ?Response {
		$stale = get_transient(
			self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_PRODUCTS
		);

		return ( $stale instanceof Response && ! $stale->isError() ) ? $stale : null;
	}

	/**
	 * Perform the live products-list request with rate-limit header capture enabled.
	 *
	 * `BB_License_Manager::capture_api_headers()` normally only listens during
	 * license operations; hooking it here records `Retry-After` /
	 * `X-RateLimit-Reset` data from products responses too, which
	 * `bb_rate_limit_seconds_remaining()` then uses to size the backoff window.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @param array $args Query args for the products list request.
	 * @return mixed The products API response.
	 */
	protected static function bb_request_products_list( array $args ) {
		$capture = array( BB_License_Manager::class, 'capture_api_headers' );
		$added   = false;

		if ( false === has_filter( 'http_response', $capture ) ) {
			add_filter( 'http_response', $capture, 10, 3 );
			$added = true;
		}

		$client = is_callable( self::$live_products_api_client )
			? self::$live_products_api_client
			: array( Products::class, 'list' );

		try {
			$response = call_user_func( $client, $args );
		} catch ( \Throwable $e ) {
			// \Throwable, not \Exception: the strictly-typed vendor client can raise
			// TypeError (e.g. a malformed error payload hitting implode()), which
			// must feed the backoff instead of fataling the transient filter.
			// An empty message would make Response::isError() read as success and
			// poison the success caches with an empty catalog — always supply one.
			$message  = $e->getMessage();
			$response = new Response(
				null,
				'' !== $message ? $message : __( 'The add-ons API request did not complete.', 'buddyboss' )
			);
		} finally {
			if ( $added ) {
				remove_filter( 'http_response', $capture, 10 );
			}
		}

		return $response;
	}

	/**
	 * Seconds until the recorded licensing API rate-limit window resets.
	 *
	 * Reads the `bb_license_rate_limit` transient written by
	 * `BB_License_Manager::capture_api_headers()`. Only an exhausted quota
	 * (`remaining` of zero with a future reset) counts as blocking; rate-limit
	 * headers on healthy responses do not pause update checks.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @return int Seconds remaining in the block, or 0 when not rate limited.
	 */
	protected static function bb_rate_limit_seconds_remaining(): int {
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
		 * X-RateLimit-Reset). Ignore it rather than let a bogus timestamp re-arm the
		 * backoff forever; the regular error backoff still debounces live failures.
		 */
		if ( $seconds > DAY_IN_SECONDS ) {
			return 0;
		}

		return max( 0, $seconds );
	}

	/**
	 * Clamp a backoff duration between the five-minute floor and the one-hour cap.
	 *
	 * @since BuddyBoss 3.4.4
	 *
	 * @param int $seconds Server-suggested wait, or 0 when unknown.
	 * @return int Backoff duration in seconds.
	 */
	protected static function bb_products_error_backoff_duration( int $seconds ): int {
		return (int) min(
			max( $seconds, self::CACHE_DURATION_PRODUCTS_ERROR ),
			self::CACHE_DURATION_PRODUCTS_ERROR_MAX
		);
	}

	/**
	 * Generates and returns the HTML for the add-ons.
	 * Overrides parent method to use our local view file.
	 *
	 * @return string The HTML for the add-ons.
	 */
	public static function generateAddonsHtml(): string {
		// Check if license is activated before making API calls.
		if ( ! self::getContainer()->get( AbstractPluginConnection::class )->getLicenseActivationStatus() ) {
			return '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Please activate your license to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		if ( ! self::getContainer()->get( AbstractPluginConnection::class )->getLicenseKey() ) {
			return '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter your license key to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		// Refresh the add-ons if the button is clicked.
		if ( isset( $_POST['submit-button-mosh-refresh-addon'] ) ) {
			delete_transient( self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_PRODUCTS );

			// Clear product add-ons cache when products are manually refreshed.
			self::clearProductAddOnsCache();
		}

		$addons = self::getAddons( true );

		if ( $addons instanceof Response && $addons->isError() ) {
			return sprintf( '<div class=""><p>%s <b>%s</b></p></div>', esc_html__( 'There was an issue connecting with the API.', 'buddyboss' ), $addons->error );
		}

		self::enqueueAssets();

		ob_start();
		$products = self::prepareProductsForDisplay( $addons->products ?? array() );
		// Use our local view file instead of the vendor's.
		include_once __DIR__ . '/views/products.php';
		return ob_get_clean();
	}

	/**
	 * Check if a product exists and is enabled by slug.
	 * Implements transient caching to reduce API calls.
	 *
	 * A null return is not proof that the product is absent from the customer's plan —
	 * it is also what a failed API call produces. Callers that turn "no product" into
	 * an upsell must consult `productsApiErrored()` first, or a network blip reads as
	 * "not in your plan" to a fully licensed customer.
	 *
	 * @param string $slug Product slug to check.
	 * @return object|null Product object if found and enabled, null otherwise.
	 */
	public static function checkProductBySlug( string $slug ): ?object {
		// Check if the license is activated before making API calls.
		if ( ! self::getContainer()->get( AbstractPluginConnection::class )->getLicenseActivationStatus() ) {
			return null;
		}

		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;
		$cache_key = $plugin_id . '_add_ons';

		$apiResponse = get_transient( $cache_key );

		if ( empty( $apiResponse ) ) {
			$apiResponse = self::getAddons( \true );

			/*
			 * An error Response carries no `products`, so caching one for the full 12
			 * hours made every downstream check read as an empty plan for half a day
			 * after a single failed request. Cache it briefly instead of not at all:
			 * callers such as bb_get_placeholder_plugin_status() call this once per
			 * catalog item, so skipping the cache entirely would fire one failing HTTP
			 * request per card on every admin page load while the API is down.
			 */
			$ttl = self::isErrorResponse( $apiResponse )
				? self::CACHE_DURATION_ADD_ONS_ERROR
				: self::CACHE_DURATION_ADD_ONS;

			set_transient( $cache_key, $apiResponse, $ttl );
		}

		$result = null;
		foreach ( $apiResponse->products ?? [] as $product ) {
			if (
				! empty( $product->slug ) &&
				strpos( $product->slug, $slug ) !== false &&
				! empty( $product->status ) &&
				'enabled' === $product->status
			) {
				$result = $product;
				break;
			}
		}

		return $result;
	}

	/**
	 * Whether a response from the add-ons API represents a failure.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @param mixed $response Response returned by `getAddons()`.
	 * @return bool True when the API did not answer successfully.
	 */
	protected static function isErrorResponse( $response ): bool {
		if ( ! $response instanceof Response ) {
			// No Response object at all means the request never completed.
			return true;
		}

		return $response->isError();
	}

	/**
	 * Whether the last add-ons API lookup failed.
	 *
	 * Lets callers tell "this product is not in your plan" apart from "we could not
	 * reach the add-ons API", which look identical through `checkProductBySlug()`.
	 * Reads the same cached response that lookup uses, so calling this alongside it
	 * costs no extra HTTP request.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @return bool True when the add-ons list could not be retrieved.
	 */
	public static function productsApiErrored(): bool {
		if ( ! self::getContainer()->get( AbstractPluginConnection::class )->getLicenseActivationStatus() ) {
			// Without an active license there is no API call to fail.
			return false;
		}

		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;
		$cached    = get_transient( $plugin_id . '_add_ons' );

		if ( ! empty( $cached ) ) {
			return self::isErrorResponse( $cached );
		}

		return self::isErrorResponse( self::getAddons( \true ) );
	}

	/**
	 * Clear all product add-ons caches.
	 * Called when license status changes or products are refreshed.
	 */
	public static function clearProductAddOnsCache(): void {
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;
		$cache_key = $plugin_id . '_add_ons';
		delete_transient( $cache_key );

		// Lift the products error backoff too, so license changes and the manual
		// "Refresh Add-ons" button allow an immediate live retry — unless an active
		// rate-limit block (bb_license_rate_limit) is still in force, in which case
		// the next call fails fast and re-caches the backoff until the reset.
		delete_transient( $plugin_id . self::CACHE_KEY_PRODUCTS_ERROR );
	}

	/**
	 * Clear cache for the addons while invalid:
	 * self::getContainer()->get(AbstractPluginConnection::class)->pluginId . self::CACHE_KEY_UPDATE_CHECK
	 *
	 * @param mixed $value New value of transient.
	 *
	 * @return mixed
	 */
	public static function pre_set_addon_update_transient( $value ) {
		if ( null === $value ) {
			self::clearProductAddOnsCache();
		}

		return $value;
	}
}
