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
			// Ensure error is a string for display.
			$error_message = is_string( $addons->error ) ? $addons->error : (string) $addons->error;
			return sprintf( '<div class=""><p>%s <b>%s</b></p></div>', esc_html__( 'There was an issue connecting with the API.', 'buddyboss' ), $error_message );
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
			set_transient( $cache_key, $apiResponse, 12 * HOUR_IN_SECONDS );
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
