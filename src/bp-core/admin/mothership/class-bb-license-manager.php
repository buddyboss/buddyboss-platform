<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;

/**
 * BuddyBoss License Manager extends the base LicenseManager
 * to add dynamic plugin ID functionality.
 */
class BB_License_Manager extends LicenseManager {

	/**
	 * The controller for handling the license activation/deactivation post requests.
	 * Overrides the parent controller to add dynamic plugin ID support.
	 *
	 * @return void
	 */
	public static function controller(): void {
		if ( isset( $_POST['buddyboss_platform_license_button'] ) ) {
			$pluginConnector = self::getContainer()->get( MothershipService::CONNECTION_PLUGIN_SERVICE_ID );

			// Setup dynamic plugin ID if present in license key
			if ( isset( $_POST['license_key'] ) ) {
				$_POST['license_key'] = self::setupDynamicPluginId( $_POST['license_key'], $pluginConnector );
			}

			$pluginId = $pluginConnector->pluginId;

			if ( $_POST['buddyboss_platform_license_button'] === 'activate' ) {
				try {
					self::activateLicense( $_POST['license_key'], $_POST['activation_domain'] );
					printf(
						'<div class="notice notice-success"><p>%s</p></div>',
						esc_html__( 'License activated successfully', 'buddyboss' )
					);
				} catch ( \Exception $e ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				}
			} elseif ( $_POST['buddyboss_platform_license_button'] === 'deactivate' ) {
				try {
					self::deactivateLicense( $_POST['license_key'], $_POST['activation_domain'] );
					printf(
						'<div class="notice notice-success"><p>%s</p></div>',
						esc_html__( 'License deactivated successfully', 'buddyboss' )
					);
				} catch ( \Exception $e ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				}
			}
		}
	}

	/**
	 * Activate a license with dynamic plugin ID support.
	 *
	 * @param string $licenseKey The license key.
	 * @param string $domain     The domain to activate on.
	 *
	 * @throws \Exception If the activation fails.
	 * @return void
	 */
	public static function activateLicense( string $licenseKey, string $domain ): void {
		// Check if the user has the necessary capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( esc_html__( 'You do not have permission to activate a license', 'buddyboss' ) );
		}

		// Check nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'mothership_activate_license' ) ) {
			throw new \Exception( esc_html__( 'Invalid nonce', 'buddyboss' ) );
		}

		$pluginConnector = self::getContainer()->get( MothershipService::CONNECTION_PLUGIN_SERVICE_ID );

		// Setup dynamic plugin ID if present in license key
		$licenseKey = self::setupDynamicPluginId( $licenseKey, $pluginConnector );

		// Translators: %s is the response error message.
		$errorHtml = esc_html__( 'License activation failed: %s', 'buddyboss' );

		try {
			$product  = $pluginConnector->pluginId;
			$response = LicenseActivations::activate( $product, $licenseKey, $domain );
		} catch ( \Exception $e ) {
			throw new \Exception(
				sprintf(
					$errorHtml,
					$e->getMessage()
				)
			);
		}

		if ( $response instanceof Response && $response->isError() ) {
			throw new \Exception(
				sprintf(
					$errorHtml,
					$response->getError()
				)
			);
		}

		if ( $response instanceof Response && ! $response->isError() ) {
			try {
				Credentials::storeLicenseKey( $licenseKey );
				$pluginConnector->updateLicenseActivationStatus( true );

				// Clear add-ons cache to force refresh
				$pluginId = $pluginConnector->pluginId;
				delete_transient( $pluginId . '-mosh-products' );
				delete_transient( $pluginId . '-mosh-addons-update-check' );
			} catch ( \Exception $e ) {
				throw new \Exception( $e->getMessage() );
			}
		}
	}

	/**
	 * Parse license key for dynamic plugin ID.
	 * License keys can have format: "LICENSE_KEY:PLUGIN_ID"
	 * where PLUGIN_ID starts with "bb-"
	 *
	 * @param string $licenseKey     The license key that may contain plugin ID.
	 * @param object $pluginConnector The plugin connector instance.
	 *
	 * @return string The cleaned license key without plugin ID.
	 */
	private static function setupDynamicPluginId( string $licenseKey, $pluginConnector ): string {
		$keyParts = explode( ':', $licenseKey );

		// Check if license key contains plugin ID in format KEY:PLUGIN_ID
		if ( count( $keyParts ) === 2 && preg_match( '/^bb-/', $keyParts[1] ) ) {
			$pluginId = $keyParts[1];

			// Store the web plugin ID
			update_option( 'buddyboss_web_plugin_id', $pluginId );

			// Set the dynamic plugin ID
			$pluginConnector->setDynamicPluginId( $pluginId );

			// Return the actual license key part
			return $keyParts[0];
		}

		return $licenseKey;
	}

	/**
	 * Generates the HTML for the activation form.
	 * Overrides parent to use BuddyBoss specific button naming.
	 *
	 * @return string The HTML for the activation form.
	 */
	public function generateActivationForm(): string {
		ob_start();
		$pluginId = self::getContainer()->get( MothershipService::CONNECTION_PLUGIN_SERVICE_ID )->pluginId;
		?>
		<h2><?php esc_html_e( 'License Activation', 'buddyboss' ); ?></h2>
		<form method="post" action="" name="<?php echo esc_attr( $pluginId ); ?>_activate_license_form">
			<div class="<?php echo esc_attr( $pluginId ); ?>-license-form license-form-wrap">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License Key', 'buddyboss' ); ?></label>
						</th>
						<td>
							<input type="text" name="license_key" id="license_key" placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss' ); ?>" value="<?php echo esc_attr( Credentials::getLicenseKey() ); ?>" >
							<input type="hidden" name="activation_domain" id="activation_domain" value="<?php echo esc_attr( Credentials::getActivationDomain() ); ?>" >
						</td>
					</tr>
					<tr>
						<td colspan="2" scope="row">
							<?php wp_nonce_field( 'mothership_activate_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="activate">
							<input type="submit" value="<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>" class="button button-primary <?php echo esc_attr( $pluginId ); ?>-button-activate">
						</td>
					</tr>
				</table>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generates the HTML for the disconnect/deactivate form.
	 * Overrides parent to use BuddyBoss specific button naming.
	 *
	 * @return string The HTML for the disconnect form.
	 */
	public function generateDisconnectForm(): string {
		ob_start();
		$pluginId     = self::getContainer()->get( MothershipService::CONNECTION_PLUGIN_SERVICE_ID )->pluginId;
		$licenseKey   = Credentials::getLicenseKey();
		$license_info = bb_get_license_details( $licenseKey );
		$activation_text = sprintf(
			__( '%1$s of %2$s sites have been activated with this license key', 'buddyboss' ),
			$license_info['total_prod_used'],
			$license_info['total_prod_allowed']
		)
		?>
		<h2><?php esc_html_e( 'Active License Information', 'buddyboss' ); ?></h2>

		<div class="activated-licence">
			<p class=""><?php esc_html_e( 'License Key: ', 'buddyboss' ); ?><?php echo esc_html( $license_info[ 'license_key' ] ); ?></p>
			<p class=""><?php esc_html_e( 'Status: ', 'buddyboss' ); ?><?php echo esc_html( $license_info[ 'status' ] ); ?></p>
			<p class=""><?php esc_html_e( 'Product: ', 'buddyboss' ); ?><?php echo esc_html( $license_info[ 'product' ] ); ?></p>
			<p class=""><?php esc_html_e( 'Activations: ', 'buddyboss' ); ?><?php echo esc_html( $activation_text ); ?></p>
		</div>
		<form method="post" action="" name="<?php echo esc_attr( $pluginId ); ?>_deactivate_license_form">
			<div class="<?php echo esc_attr( $pluginId ); ?>-license-form license-form-wrap">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License Key', 'buddyboss' ); ?></label>
						</th>
						<td>
							<input type="text" name="license_key" id="license_key" placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss' ); ?>" value="<?php echo esc_attr( $licenseKey ); ?>" readonly />
							<input type="hidden" name="activation_domain" id="activation_domain" value="<?php echo esc_attr( Credentials::getActivationDomain() ); ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2" scope="row">
							<?php wp_nonce_field( 'mothership_deactivate_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="deactivate">
							<input type="submit" value="<?php esc_html_e( 'Deactivate License', 'buddyboss' ); ?>" class="button button-secondary <?php echo esc_attr( $pluginId ); ?>-button-deactivate" >
						</td>
					</tr>
				</table>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}
}
