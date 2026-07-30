<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	protected const CACHE_DURATION_ADD_ONS_ERROR = 2 * MINUTE_IN_SECONDS;

	public static function loadHooks(): void {
		parent::loadHooks();

		$cache_products_key = self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_PRODUCTS;
		$cache_update_key   = self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_UPDATE_CHECK;

		add_filter( 'pre_set_transient_' . $cache_update_key, [ self::class, 'pre_set_addon_update_transient' ], 10, 1 );
		add_action( 'delete_transient_' . $cache_products_key, [ self::class, 'clearProductAddOnsCache' ] );
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
