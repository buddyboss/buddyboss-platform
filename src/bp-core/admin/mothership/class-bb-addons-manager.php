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
	 * Generates and returns the HTML for the add-ons.
	 * Overrides parent method to use our local view file.
	 *
	 * @return string The HTML for the add-ons.
	 */
	public static function generateAddonsHtml(): string {
		if ( ! self::getContainer()->get( AbstractPluginConnection::class )->getLicenseKey() ) {
			return '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter your license key to access add-ons.', 'buddyboss' ) . '</p></div>';
		}

		// Refresh the add-ons if the button is clicked.
		if ( isset( $_POST['submit-button-mosh-refresh-addon'] ) ) {
			delete_transient( self::getContainer()->get( AbstractPluginConnection::class )->pluginId . self::CACHE_KEY_PRODUCTS );
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

	public static function checkProductBySlug( string $slug ): ?object {
		$apiResponse = self::getAddons( \true );
		$result      = null;
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
}
