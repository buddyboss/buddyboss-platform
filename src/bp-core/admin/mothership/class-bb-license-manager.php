<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;

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
			$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

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

		$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

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
					$response->__get('error')
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
		$pluginId = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;
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
							<p class="description">
								<?php
									printf(
										/* translators: %s is the link to get a free license key */
										esc_html__( 'Don\'t have a license yet? Click  %s to get your free license key and receive plugin updates.', 'buddyboss' ),
										'<a href="#" id="get-free-license-link" rel="noopener noreferrer">' . __( 'here', 'buddyboss' ) . '</a>'
									);
								?>
							</p>
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

		<!-- Free License Key Modal -->
		<div id="free-license-modal" class="bb-license-modal" style="display: none;">
			<div class="bb-modal-content">
				<div class="bb-modal-header">
					<h3><?php esc_html_e( 'Get Your BuddyBoss Platform License Key', 'buddyboss' ); ?></h3>
					<span class="bb-modal-close">&times;</span>
				</div>
				<div class="bb-modal-body">
					<form id="free-license-form">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="first_name"><?php esc_html_e( 'First Name', 'buddyboss' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" name="first_name" id="first_name" required class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="last_name"><?php esc_html_e( 'Last Name', 'buddyboss' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" name="last_name" id="last_name" required class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="email"><?php esc_html_e( 'Email Address', 'buddyboss' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="email" name="email" id="email" required class="regular-text" />
								</td>
							</tr>
						</table>
						<div class="bb-modal-footer">
							<button type="submit" class="button button-primary" id="submit-license-request">
								<?php esc_html_e( 'Get License Key', 'buddyboss' ); ?>
							</button>
						</div>
					</form>
					<div id="license-response" style="display: none;">
						<p id="license-success-message"></p>
					</div>
				</div>
			</div>
		</div>


		<script>
		jQuery(document).ready(function($) {
			// Open modal
			$('#get-free-license-link').on('click', function(e) {
				e.preventDefault();
				$('#free-license-modal').show();
			});

			// Close modal
			$('.bb-modal-close').on('click', function() {
				$('#free-license-form').show();
				$('#free-license-modal').hide();
				$('#license-response').hide();
				$('#free-license-form')[0].reset();
			});

			// Close modal when clicking outside
			$(window).on('click', function(e) {
				if (e.target.id === 'free-license-modal') {
					$('#free-license-form').show();
					$('#free-license-modal').hide();
					$('#license-response').hide();
					$('#free-license-form')[0].reset();
				}
			});

			// Handle form submission
			$('#free-license-form').on('submit', function(e) {
				e.preventDefault();

				var $submitBtn = $('#submit-license-request');
				var originalText = $submitBtn.text();

				// Show loading state
				$submitBtn.text('<?php esc_html_e( 'Processing...', 'buddyboss' ); ?>').prop('disabled', true);
				$('#license-response').hide();

				// Get form data
				var formData = {
					action: 'bb_get_free_license',
					first_name: $('#first_name').val(),
					last_name: $('#last_name').val(),
					email: $('#email').val(),
					nonce: '<?php echo wp_create_nonce( 'bb_get_free_license' ); ?>'
				};

				// Make AJAX request
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							$('#license-success-message').html(response.data.message);
							$('#license-response').show();
							$('#free-license-form').get(0).reset();
							$('#free-license-form').hide();

							// If license key is provided, populate the license key field
							if (response.data.license_key) {
								$('#license_key').val(response.data.license_key);
							}
						} else {
							$('#license-success-message').html('<strong><?php esc_html_e( 'Error:', 'buddyboss' ); ?></strong> ' + response.data);
							$('#license-response').show();
						}
					},
					error: function() {
						$('#license-success-message').html('<strong><?php esc_html_e( 'Error:', 'buddyboss' ); ?></strong> <?php esc_html_e( 'An error occurred while processing your request.', 'buddyboss' ); ?>');
						$('#license-response').show();
					},
					complete: function() {
						// Reset button state
						$submitBtn.text(originalText).prop('disabled', false);
					}
				});
			});

			$(document).on('click', '.bb-free-license-key', function() {
				var $this = $(this);
				var licenseKey = $this.text().trim();

				// Create a temporary textarea to copy the text
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(licenseKey).select();

				try {
					// Copy to clipboard
					document.execCommand('copy');

					// Visual feedback
					$this.addClass('copied');

					// Remove the copied class after 2 seconds
					setTimeout(function() {
						$this.removeClass('copied');
					}, 2000);

					// Show success message
					console.log('License key copied to clipboard');

				} catch (err) {
					console.error('Failed to copy license key: ', err);
				}

				// Remove temporary textarea
				$temp.remove();
			});

		});
		</script>
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
		$pluginId     = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;
		$licenseKey   = Credentials::getLicenseKey();
		$license_info = $this->bb_get_license_details( $licenseKey );
		?>
		<h2><?php esc_html_e( 'Active License Information', 'buddyboss' ); ?></h2>

		<?php
		if ( ! is_wp_error( $license_info ) ) {
			$activation_text = sprintf(
				__( '%1$s of %2$s sites have been activated with this license key', 'buddyboss' ),
				$license_info['total_prod_used'],
				999 <= (int)$license_info['total_prod_allowed'] ? 'unlimited' : $license_info['total_prod_allowed']
			);
			?>
			<div class="activated-licence">
				<p class=""><?php esc_html_e( 'License Key: ', 'buddyboss' ); ?><?php echo esc_html( $license_info['license_key'] ); ?></p>
				<p class=""><?php esc_html_e( 'Status: ', 'buddyboss' ); ?><?php echo esc_html( $license_info['status'] ); ?></p>
				<p class=""><?php esc_html_e( 'Product: ', 'buddyboss' ); ?><?php echo esc_html( $license_info['product'] ); ?></p>
				<p class=""><?php esc_html_e( 'Activations: ', 'buddyboss' ); ?><?php echo esc_html( $activation_text ); ?></p>
			</div>
		<?php } ?>

		<form method="post" action="" name="<?php echo esc_attr( $pluginId ); ?>_deactivate_license_form">
			<div class="<?php echo esc_attr( $pluginId ); ?>-license-form license-form-wrap">
				<table class="form-table">
					<tr>
						<td colspan="2" scope="row">
							<input type="hidden" name="license_key" id="license_key" placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss' ); ?>" value="<?php echo esc_attr( $licenseKey ); ?>" readonly />
							<input type="hidden" name="activation_domain" id="activation_domain" value="<?php echo esc_attr( Credentials::getActivationDomain() ); ?>" />
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

	/**
	 * Get License + Activations details from Caseproof API
	 *
	 * @param string $license_key License UUID.
	 * @param bool   $force_refresh Whether to force refresh the cache.
	 *
	 * @return array|WP_Error    Array of license + activation data, or WP_Error on failure.
	 */
	protected function bb_get_license_details( $license_key, $force_refresh = false ) {
		$pluginId = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;

		// Create cache key based on plugin ID only (not license key for security).
		$cache_key = $pluginId . '_license_details';

		// Check cache first unless force refresh is requested.
		if ( ! $force_refresh ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data && ! is_wp_error( $cached_data ) ) {
				return $cached_data;
			}
		}

		$root_api_url = defined( strtoupper( $pluginId . '_MOTHERSHIP_API_BASE_URL' ) )
			? constant( strtoupper( $pluginId . '_MOTHERSHIP_API_BASE_URL' ) )
			: 'https://licenses.caseproof.com/api/v1/';

		$api_url     = $root_api_url . 'licenses/' . $license_key;
		$domain      = wp_parse_url( home_url(), PHP_URL_HOST );
		$credentials = base64_encode( $domain . ':' . $license_key );
		$args        = array(
			'headers' => array(
				'Authorization' => "Basic $credentials",
				'Content-Type'  => 'application / json',
				'Accept'        => 'application / json',
			),
		);

		// First request: License details.
		$response = wp_remote_get( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response; // Return error.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if (
			empty( $body['license_key'] ) ||
			empty( $body['product'] )
		) {
			return new \WP_Error( 'invalid_response', 'License key or product not found in response' );
		}

		$license_key          = $body['license_key'];
		$product              = $body['product'];
		$activations_meta_url = $body['_links']['activations-meta']['href'] ?? '';

		// If activations-meta link missing.
		if ( empty( $activations_meta_url ) ) {
			return new \WP_Error( 'missing_link', 'Activations - meta URL not found in license response' );
		}

		// Second request: Activations-meta.
		$response2 = wp_remote_get( $activations_meta_url, $args );

		if ( is_wp_error( $response2 ) ) {
			return $response2;
		}

		$body2 = json_decode( wp_remote_retrieve_body( $response2 ), true );

		// Prepare combined data.
		$license_data = array(
			'license_key'        => '********-****-****-****-' . esc_html( substr( $license_key, - 12 ) ),
			'product'            => $product,
			'status'             => $body['status'] ?? '',
			'total_prod_allowed' => $body2['prod']['allowed'] ?? 0,
			'total_prod_used'    => $body2['prod']['used'] ?? 0,
			'total_prod_free'    => $body2['prod']['free'] ?? 0,
			'total_test_allowed' => $body2['test']['allowed'] ?? 0,
			'total_test_used'    => $body2['test']['used'] ?? 0,
			'total_test_free'    => $body2['test']['free'] ?? 0,
		);

		// License details don't change frequently, so 1 Day is reasonable.
		set_transient( $cache_key, $license_data, DAY_IN_SECONDS );

		return $license_data;
	}

	/**
	 * AJAX handler for getting free license key.
	 *
	 * @return void
	 */
	public static function ajax_get_free_license(): void {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_get_free_license' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'buddyboss' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'buddyboss' ) );
		}

		// Get form data
		$first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
		$last_name   = sanitize_text_field( $_POST['last_name'] ?? '' );
		$email       = sanitize_email( $_POST['email'] ?? '' );

		// Validate required fields
		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
			wp_send_json_error( __( 'All fields are required', 'buddyboss' ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address', 'buddyboss' ) );
		}

		// Prepare API request data
		$api_data = array(
			'email'      => $email,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);

		// Make API request
		$api_url = 'https://b6zdd3mwkj.execute-api.us-east-2.amazonaws.com/v1/verify/';
		$args    = array(
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $api_data ),
		);

		$response = wp_remote_request( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				sprintf(
					__( 'API request failed: %s', 'buddyboss' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			wp_send_json_error(
				sprintf(
					__( 'API returned error code: %d', 'buddyboss' ),
					$response_code
				)
			);
		}

		$data = json_decode( $response_body, true );

		if ( ! $data ) {
			wp_send_json_error( __( 'Invalid response from API', 'buddyboss' ) );
		}

		// Check if API returned success
		if ( isset( $data['success'] ) && $data['success'] ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'License key generated successfully!', 'buddyboss' );
			$license_key = isset( $data['license_key'] ) ? $data['license_key'] : '';

			wp_send_json_success( array(
				'message'     => $message,
				'license_key' => $license_key,
			) );
		} else {
			$error_message = isset( $data['message'] ) ? $data['message'] : __( 'Failed to generate license key', 'buddyboss' );
			wp_send_json_error( $error_message );
		}
	}
}
