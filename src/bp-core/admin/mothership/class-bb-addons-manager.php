<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;

/**
 * BuddyBoss add-ons manager (static facade over the GroundLevel AddonsManager).
 *
 * GroundLevel 7.4.0 turned {@see AddonsManager} into an instance service with a five-argument
 * constructor, resolved from the container via auto-wiring. BuddyBoss calls its add-ons manager
 * statically throughout the codebase (admin page, DRM add-on gating, placeholder cards, and the
 * plugin connector's cache invalidation), so this class keeps a static facade and delegates to
 * the container-managed vendor instance.
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
	 * @return AddonsManager
	 */
	private static function addons_manager(): AddonsManager {
		return self::container()->get( AddonsManager::class );
	}

	/**
	 * Resolve the plugin connection from the container.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return AbstractPluginConnection
	 */
	private static function plugin_connection(): AbstractPluginConnection {
		return self::container()->get( AbstractPluginConnection::class );
	}

	/**
	 * Generates and returns the HTML for the add-ons using BuddyBoss's local view.
	 *
	 * @return string The HTML for the add-ons.
	 */
	public static function render_addons_html(): string {
		$plugin = self::plugin_connection();

		// Check if license is activated before making API calls.
		if ( ! $plugin->getLicenseActivationStatus() ) {
			return '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Please activate your license to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		if ( ! $plugin->getLicenseKey() ) {
			return '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter your license key to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		$addons_manager = self::addons_manager();

		// Refresh the add-ons if the button is clicked (nonce-verified).
		if (
			isset( $_POST['submit-button-mosh-refresh-addon'], $_POST['bb_mosh_refresh_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bb_mosh_refresh_nonce'] ) ), 'bb_mosh_refresh_addons' )
		) {
			$addons_manager->clearCache();
		}

		$addons = $addons_manager->getAddons( true );

		if ( $addons instanceof Response && $addons->isError() ) {
			return sprintf( '<div class=""><p>%s <b>%s</b></p></div>', esc_html__( 'There was an issue connecting with the API.', 'buddyboss' ), esc_html( $addons->getMessage() ) );
		}

		$addons_manager->enqueueAssets();

		// Reuse the vendor's display-prep logic. It is protected on AddonsManager, so we call
		// it on a container-resolved instance of this subclass (legal from within the class).
		$products = self::container()->get( self::class )->prepareProductsForDisplay( // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			$addons->getData( 'products', array() )
		);
		ob_start();
		include __DIR__ . '/views/products.php';
		return ob_get_clean();
	}

	/**
	 * Check if a product exists and is enabled by slug.
	 *
	 * Reads from the vendor add-ons cache ({@see AddonsManager::getAddons()}); no separate
	 * BuddyBoss cache layer is maintained.
	 *
	 * @param string $slug Product slug to check.
	 * @return object|null Product object if found and enabled, null otherwise.
	 */
	public static function checkProductBySlug( string $slug ): ?object {
		// Check if the license is activated before making API calls.
		if ( ! self::plugin_connection()->getLicenseActivationStatus() ) {
			return null;
		}

		$response = self::addons_manager()->getAddons( true );

		if ( ! $response instanceof Response || $response->isError() ) {
			return null;
		}

		foreach ( $response->getData( 'products', array() ) as $product ) {
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
		$plugin_id = self::plugin_connection()->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		delete_transient( $plugin_id . self::CACHE_KEY_ADDONS );
		delete_site_transient( $plugin_id . self::CACHE_KEY_ADDONS );
	}
}
