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
	 * Initialize hooks to capture API response headers.
	 * Should be called early in the WordPress lifecycle.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Hook into HTTP API to capture rate limit headers from Caseproof API responses.
		// Only register in admin context or during AJAX to prevent overhead on frontend requests.
		if ( is_admin() || wp_doing_ajax() ) {
			add_filter( 'http_response', array( __CLASS__, 'capture_api_headers' ), 10, 3 );
		}
	}

	/**
	 * Capture rate limit headers from Caseproof API responses.
	 * This filter runs for ALL HTTP requests made through wp_remote_* functions.
	 *
	 * @param array  $response HTTP response.
	 * @param array  $args     HTTP request arguments.
	 * @param string $url      The request URL.
	 *
	 * @return array Unmodified response (we only read headers).
	 */
	public static function capture_api_headers( $response, $args, $url ) {
		// Only process responses from the Caseproof license API.
		if ( false === strpos( $url, 'licenses.caseproof.com' ) ) {
			return $response;
		}

		// Skip if response is an error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check for Cloudflare errors using multiple detection signals.
		$body        = wp_remote_retrieve_body( $response );
		$status_code = wp_remote_retrieve_response_code( $response );

		if ( ! empty( $body ) ) {
			$body_lower = strtolower( $body );

			// Signal 1: HTTP status code 429 or 503.
			$is_rate_limit_status = in_array( $status_code, array( 429, 503 ), true );

			// Signal 2: Cloudflare error codes (case-insensitive).
			$has_cloudflare_error = (
				false !== strpos( $body_lower, 'error 1015' ) ||
				false !== strpos( $body_lower, 'error 1014' ) ||
				false !== strpos( $body_lower, 'error 1020' )
			);

			// Signal 3: Rate limit keywords (case-insensitive).
			$has_rate_limit_text = (
				false !== strpos( $body_lower, 'rate limit' ) ||
				false !== strpos( $body_lower, 'too many requests' )
			);

			// Signal 4: Cloudflare branding.
			$is_cloudflare = false !== strpos( $body_lower, 'cloudflare' );

			// Detect Cloudflare rate limit with multiple signal validation.
			$cloudflare_detected = (
				$has_cloudflare_error ||
				( $is_rate_limit_status && $is_cloudflare ) ||
				( $has_rate_limit_text && $is_cloudflare )
			);

			if ( $cloudflare_detected ) {
				// Cloudflare rate limit detected - store extended block (60 minutes).
				bb_error_log(
					'BuddyBoss: Cloudflare rate limit detected - blocking for 60 minutes',
					true
				);

				$reset_time      = time() + 3600; // 60 minutes.
				$rate_limit_data = array(
					'limit'     => null,
					'remaining' => 0,
					'reset'     => $reset_time,
					'source'    => 'cloudflare_detected',
					'timestamp' => time(),
				);

				self::set_rate_limit_transient(
					'buddyboss_license_rate_limit',
					$rate_limit_data,
					3660 // 61 minutes.
				);

				return $response;
			}
		}

		// Extract headers.
		$headers = wp_remote_retrieve_headers( $response );

		if ( empty( $headers ) ) {
			return $response;
		}

		// Convert headers to array format (WP_HTTP_Requests_Response may return object).
		if ( is_object( $headers ) && method_exists( $headers, 'getAll' ) ) {
			$headers = $headers->getAll();
		} elseif ( is_object( $headers ) ) {
			$headers = (array) $headers;
		}

		// Make headers case-insensitive for easier access.
		$headers = array_change_key_case( $headers, CASE_LOWER );

		// Extract all rate limit headers.
		$limit           = isset( $headers['x-ratelimit-limit'] ) ? (int) $headers['x-ratelimit-limit'] : null;
		$remaining       = isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : null;
		$reset_timestamp = isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : null;
		$retry_after     = isset( $headers['retry-after'] ) ? (int) $headers['retry-after'] : null;

		// Check if we have any rate limit headers.
		$has_rate_limit_data = (
			null !== $limit ||
			null !== $remaining ||
			null !== $reset_timestamp ||
			null !== $retry_after
		);

		if ( $has_rate_limit_data ) {
			// Log rate limit info only on 429 errors.
			if ( 429 === $status_code ) {
				$reset_info = '';
				if ( null !== $reset_timestamp ) {
					$reset_info = sprintf(
						' - Reset: %s',
						date( 'Y-m-d H:i:s', $reset_timestamp )
					);
				} elseif ( null !== $retry_after ) {
					$reset_info = sprintf( ' - Reset in %d seconds', $retry_after );
				}

				bb_error_log(
					sprintf(
						'Rate limit exceeded (remaining: %s)%s',
						null !== $remaining ? $remaining : 'N/A',
						$reset_info
					),
					true
				);
			}

			// Determine the best reset time to use.
			$final_reset_time = null;
			$source           = 'unknown';

			// Priority: X-RateLimit-Reset > Retry-After calculation.
			if ( null !== $reset_timestamp ) {
				$final_reset_time = $reset_timestamp;
				$source           = 'x_ratelimit_reset';
			} elseif ( null !== $retry_after ) {
				$final_reset_time = time() + $retry_after;
				$source           = 'retry_after_header';
			}

			// Build rate limit data.
			$rate_limit_data = array(
				'limit'     => $limit,
				'remaining' => null !== $remaining ? $remaining : ( 429 === $status_code ? 0 : null ),
				'reset'     => $final_reset_time,
				'source'    => $source,
				'timestamp' => time(),
			);

			// Calculate expiration for transient.
			$expiration = HOUR_IN_SECONDS;
			if ( $final_reset_time ) {
				$time_until_reset = max( 0, $final_reset_time - time() );
				$expiration       = $time_until_reset + 60; // Add 60 second buffer.
			}

			self::set_rate_limit_transient(
				'buddyboss_license_rate_limit',
				$rate_limit_data,
				$expiration
			);

			// Rate limit data stored successfully.
		}

		return $response;
	}

	/**
	 * The controller for handling the license activation/deactivation post requests.
	 * Overrides the parent controller to add dynamic plugin ID support.
	 *
	 * @return void
	 */
	public static function controller(): void {
		if ( isset( $_POST['buddyboss_platform_license_button'] ) ) {
			$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

			// Setup dynamic plugin ID if present in license key.
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

		// Validate inputs.
		if ( empty( $licenseKey ) ) {
			throw new \Exception( esc_html__( 'License key is required', 'buddyboss' ) );
		}

		if ( empty( $domain ) ) {
			throw new \Exception( esc_html__( 'Activation domain is required', 'buddyboss' ) );
		}

		// Check if we're being rate limited (proactive check).
		$rate_limit_check = self::checkRateLimit();
		if ( is_wp_error( $rate_limit_check ) ) {
			throw new \Exception( $rate_limit_check->get_error_message() );
		}

		$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

		// Setup dynamic plugin ID if present in license key.
		$licenseKey = self::setupDynamicPluginId( $licenseKey, $pluginConnector );

		// Validate product matches license before activation.
		$validation_error = self::validateProductBeforeActivation( $licenseKey, $pluginConnector->pluginId );
		if ( is_wp_error( $validation_error ) ) {
			$pluginConnector->clearDynamicPluginId();

			throw new \Exception( $validation_error->get_error_message() );
		}

		// Translators: %s is the response error message.
		$errorHtml = esc_html__( 'License activation failed: %s', 'buddyboss' );

		try {
			$product = $pluginConnector->pluginId;

			// Log activation attempt for debugging.
			bb_error_log(
				sprintf(
					'BuddyBoss: Attempting license activation - Product: %s, Domain: %s',
					$product,
					$domain
				),
				true
			);

			$response = LicenseActivations::activate( $product, $licenseKey, $domain );
		} catch ( \Exception $e ) {
			bb_error_log( sprintf( 'License activation failed: %s', $e->getMessage() ), true );
			throw new \Exception(
				sprintf(
					$errorHtml,
					$e->getMessage()
				)
			);
		}

		if ( $response instanceof Response && $response->isError() ) {
			$errorCode    = $response->__get( 'errorCode' );
			$errorMessage = $response->__get( 'error' );
			$errors       = $response->__get( 'errors' );

			// Track failed activation for exponential backoff.
			self::trackFailedActivation();

			// Log the error details.
			bb_error_log(
				sprintf(
					'BuddyBoss: License activation failed - Code: %d, Message: %s, Product: %s',
					$errorCode,
					$errorMessage,
					$pluginConnector->pluginId
				),
				true
			);

			// Handle 422 - Product validation failed.
			if ( 422 === $errorCode ) {
				// Check if it's a product mismatch error.
				if ( is_array( $errors ) && isset( $errors['product'] ) ) {
					// Clear the orphaned dynamic plugin ID.
					$pluginConnector->clearDynamicPluginId();
					bb_error_log( 'Cleared orphaned plugin ID (422)', true );

					throw new \Exception(
						esc_html__( 'License activation failed: The stored product ID did not match your license. Please try activating again with your license key.', 'buddyboss' )
					);
				}
			}

			// Handle 429 - Rate limit exceeded with exponential backoff.
			if ( 429 === $errorCode ) {
				// Check if HTTP filter captured rate limit headers from this 429 response.
				$rateLimitData = self::get_rate_limit_transient( 'buddyboss_license_rate_limit' );

				// Use actual reset time from captured headers if available, otherwise use exponential backoff.
				if ( $rateLimitData && isset( $rateLimitData['reset'] ) && $rateLimitData['reset'] > 0 ) {
					$resetTime   = $rateLimitData['reset'];
					$backoffTime = max( 0, $resetTime - time() );
					$waitMinutes = ceil( $backoffTime / 60 );
					$source      = isset( $rateLimitData['source'] ) ? $rateLimitData['source'] : 'unknown';

					bb_error_log( 'Using API reset time', true );
				} else {
					$backoffTime = self::getBackoffWaitTime();
					$resetTime   = time() + $backoffTime;
					$waitMinutes = ceil( $backoffTime / 60 );

					bb_error_log( 'Using calculated backoff time', true );
				}

				bb_error_log(
					sprintf(
						'Wait %d minutes (reset: %s)',
						$waitMinutes,
						date( 'Y-m-d H:i:s', $resetTime )
					),
					true
				);

				// Update rate limit to ensure we wait the backoff time.
				$rateLimitData = array(
					'limit'     => isset( $actualRateLimitInfo['limit'] ) ? $actualRateLimitInfo['limit'] : 10,
					'remaining' => isset( $actualRateLimitInfo['remaining'] ) ? $actualRateLimitInfo['remaining'] : 0,
					'reset'     => $resetTime,
					'timestamp' => time(),
					'source'    => $actualRateLimitInfo ? 'api_headers' : 'calculated_backoff',
				);
				self::set_rate_limit_transient( 'buddyboss_license_rate_limit', $rateLimitData, HOUR_IN_SECONDS );

				throw new \Exception(
					sprintf(
					/* translators: %d is the number of minutes to wait */
						esc_html__( 'License activation failed: Too many activation requests. Please wait approximately %d minute(s) before trying again.', 'buddyboss' ),
						max( 1, $waitMinutes )
					)
				);
			}

			// Generic error handling.
			throw new \Exception(
				sprintf(
					$errorHtml,
					$errorMessage
				)
			);
		}

		if ( $response instanceof Response && ! $response->isError() ) {
			try {
				Credentials::storeLicenseKey( $licenseKey );
				$pluginConnector->updateLicenseActivationStatus( true );

				// Clear all caches to force refresh.
				// Note: updateLicenseActivationStatus already clears license details and add-ons cache,.
				// but we explicitly clear product transients here for consistency.
				$pluginId = $pluginConnector->pluginId;
				delete_transient( $pluginId . '-mosh-products' );
				delete_transient( $pluginId . '-mosh-addons-update-check' );

				// Reset failed attempts counter on successful activation.
				self::resetFailedAttempts();

				// Log successful activation.
				bb_error_log(
					sprintf(
						'BuddyBoss: License activated successfully - Product: %s, Domain: %s',
						$pluginId,
						$domain
					),
					true
				);
			} catch ( \Exception $e ) {
				bb_error_log( sprintf( 'Error storing license: %s', $e->getMessage() ), true );
				throw new \Exception( $e->getMessage() );
			}
		}
	}

	/**
	 * Validate that the product ID matches the license before activation.
	 * Prevents activation failures due to product mismatch.
	 *
	 * @param string $licenseKey The license key to validate.
	 * @param string $productId  The product ID we're attempting to activate.
	 *
	 * @return true|WP_Error True if validation passes, WP_Error if fails.
	 */
	private static function validateProductBeforeActivation( string $licenseKey, string $productId ) {
		// Skip validation if license key is empty (will be caught by input validation).
		if ( empty( $licenseKey ) ) {
			return true;
		}

		// Skip validation if we have any recent failed attempts (likely rate limited).
		// This preserves API calls for the actual activation attempt.
		$failedAttempts = self::get_rate_limit_transient( 'buddyboss_license_failed_attempts' );
		if ( $failedAttempts && (int) $failedAttempts > 0 ) {
			bb_error_log(
				sprintf(
					'BuddyBoss: Skipping pre-activation validation - %d recent failed attempts, preserving API calls',
					$failedAttempts
				)
			);
			return true;
		}

		// Also skip if we're currently rate limited (don't waste an API call).
		$rateLimitData = self::get_rate_limit_transient( 'buddyboss_license_rate_limit' );
		if ( $rateLimitData && is_array( $rateLimitData ) ) {
			$resetTime   = isset( $rateLimitData['reset'] ) ? (int) $rateLimitData['reset'] : 0;
			$currentTime = time();

			// Check if we're currently in a rate limit block (reset time is in the future).
			if ( $resetTime > 0 && $currentTime < $resetTime ) {
				$waitMinutes = ceil( max( 0, $resetTime - $currentTime ) / 60 );
				bb_error_log(
					sprintf(
						'Skipping pre-validation - rate limited until %s (%d minutes)',
						date( 'Y-m-d H:i:s', $resetTime ),
						$waitMinutes
					),
					true
				);
				return true;
			}

			// Also check remaining count if available.
			$remaining = isset( $rateLimitData['remaining'] ) ? (int) $rateLimitData['remaining'] : null;
			if ( $remaining !== null && $remaining <= 1 ) {
				// Too close to rate limit - skip validation to preserve API call for activation.
				bb_error_log(
					sprintf(
						'Skipping pre-validation - only %d requests remaining',
						$remaining
					)
				);
				return true;
			}
		}

		// Pre-validation started.
		try {
			// Fetch license details from API.
			$response = \BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Licenses::get( $licenseKey );

			if ( $response instanceof Response && $response->isError() ) {
				$errorCode = $response->__get( 'errorCode' );

				// If validation gets a 429, we're rate limited - store this info and block activation.
				if ( 429 === $errorCode ) {
					bb_error_log( 'Validation encountered rate limit - blocking activation', true );

					// Track this as a failed attempt.
					self::trackFailedActivation();

					// Check if HTTP filter already captured the ACTUAL reset time from API headers.
					$rateLimitData = self::get_rate_limit_transient( 'buddyboss_license_rate_limit' );

					// Use actual reset time from captured headers if available, otherwise use exponential backoff.
					if ( $rateLimitData && isset( $rateLimitData['reset'] ) && $rateLimitData['reset'] > 0 ) {
						$resetTime   = $rateLimitData['reset'];
						$backoffTime = max( 0, $resetTime - time() );
						$source      = isset( $rateLimitData['source'] ) ? $rateLimitData['source'] : 'unknown';

						bb_error_log( 'Using API reset time', true );
					} else {
						$backoffTime = self::getBackoffWaitTime();
						$resetTime   = time() + $backoffTime;

						bb_error_log( 'Using calculated backoff time', true );
					}

					$waitMinutes = (int) ceil( $backoffTime / 60 );

					bb_error_log(
						sprintf(
							'Rate limit detected during validation - wait time: %d seconds (%d minutes)',
							$backoffTime,
							$waitMinutes
						),
						true
					);

					bb_error_log(
						sprintf(
							'Reset time: %s (Unix: %d)',
							date( 'Y-m-d H:i:s', $resetTime ),
							$resetTime
						),
						true
					);

					// Store/update rate limit data (don't overwrite if we have actual API data).
					if ( ! $rateLimitData || ! isset( $rateLimitData['reset'] ) ) {
						set_transient(
							'buddyboss_license_rate_limit',
							array(
								'remaining' => 0,
								'reset'     => $resetTime,
								'source'    => 'validation_calculated',
							),
							$backoffTime + 60 // Add 60 seconds buffer.
						);
					}

					return new \WP_Error(
						'rate_limit',
						sprintf(
							/* translators: %d is the number of minutes to wait */
							esc_html__( 'Too many activation requests. Please wait approximately %d minute(s) before trying again.', 'buddyboss' ),
							max( 1, $waitMinutes )
						)
					);
				}

				// Don't block activation on other validation errors - let the activation endpoint handle it.
				bb_error_log(
					sprintf(
						'BuddyBoss: Pre-activation validation failed to fetch license (non-blocking): %s',
						$response->__get( 'error' )
					)
				);
				return true;
			}

			if ( $response instanceof Response && ! $response->isError() ) {
				$licenseData = $response->toArray();

				// Check if product field exists.
				if ( isset( $licenseData['product'] ) ) {
					$actualProduct = $licenseData['product'];

					bb_error_log(
						sprintf(
							'BuddyBoss: License product validation - Expected: %s, Actual: %s',
							$productId,
							$actualProduct
						)
					);

					// If product doesn't match, this is likely an orphaned plugin ID.
					if ( $actualProduct !== $productId ) {
						bb_error_log(
							sprintf(
								'BuddyBoss: Product mismatch detected before activation - clearing orphaned plugin ID'
							),
							true
						);

						// Get plugin connector to clear the orphaned ID.
						$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );
						$pluginConnector->clearDynamicPluginId();

						return new \WP_Error(
							'product_mismatch',
							sprintf(
								/* translators: 1: Expected product, 2: Actual product from license */
								esc_html__( 'Product validation failed: Your license is for "%2$s" but the system was configured for "%1$s". The configuration has been reset. Please try activating again.', 'buddyboss' ),
								$productId,
								$actualProduct
							)
						);
					}

					// Product matches - validation passed.
					// Product validation passed.
					return true;
				}
			}
		} catch ( \Exception $e ) {
			// Don't block activation on validation errors - let the activation endpoint handle it.
			bb_error_log(
				sprintf(
					'BuddyBoss: Pre-activation validation exception (non-blocking): %s',
					$e->getMessage()
				)
			);
			return true;
		}

		// If we couldn't validate, allow activation to proceed.
		return true;
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

		// Check if license key contains plugin ID in format KEY:PLUGIN_ID.
		if ( count( $keyParts ) === 2 && preg_match( '/^bb-/', $keyParts[1] ) ) {
			$pluginId = $keyParts[1];

			// Validate plugin ID format for security - only lowercase letters, numbers, and hyphens.
			if ( ! preg_match( '/^[a-z0-9\-]+$/', $pluginId ) ) {
				throw new \Exception( esc_html__( 'Invalid plugin ID format in license key', 'buddyboss' ) );
			}

			// Store the web plugin ID.
			update_option( 'buddyboss_web_plugin_id', $pluginId );

			// Set the dynamic plugin ID.
			$pluginConnector->setDynamicPluginId( $pluginId );

			// Return the actual license key part.
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
			// Open modal.
			$('#get-free-license-link').on('click', function(e) {
				e.preventDefault();
				$('#free-license-modal').show();
			});

			// Close modal.
			$('.bb-modal-close').on('click', function() {
				$('#free-license-form').show();
				$('#free-license-modal').hide();
				$('#license-response').hide();
				$('#free-license-form')[0].reset();
			});

			// Close modal when clicking outside.
			$(window).on('click', function(e) {
				if (e.target.id === 'free-license-modal') {
					$('#free-license-form').show();
					$('#free-license-modal').hide();
					$('#license-response').hide();
					$('#free-license-form')[0].reset();
				}
			});

			// Handle form submission.
			$('#free-license-form').on('submit', function(e) {
				e.preventDefault();

				var $submitBtn = $('#submit-license-request');
				var originalText = $submitBtn.text();

				// Show loading state.
				$submitBtn.text('<?php esc_html_e( 'Processing...', 'buddyboss' ); ?>').prop('disabled', true);
				$('#license-response').hide();

				// Get form data.
				var formData = {
					action: 'bb_get_free_license',
					first_name: $('#first_name').val(),
					last_name: $('#last_name').val(),
					email: $('#email').val(),
					nonce: '<?php echo wp_create_nonce( 'bb_get_free_license' ); ?>'
				};

				// Make AJAX request.
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

							// If license key is provided, populate the license key field.
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
						// Reset button state.
						$submitBtn.text(originalText).prop('disabled', false);
					}
				});
			});

			$(document).on('click', '.bb-free-license-key', function() {
				var $this = $(this);
				var licenseKey = $this.text().trim();

				// Create a temporary textarea to copy the text.
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(licenseKey).select();

				try {
					// Copy to clipboard.
					document.execCommand('copy');

					// Visual feedback.
					$this.addClass('copied');

					// Remove the copied class after 2 seconds.
					setTimeout(function() {
						$this.removeClass('copied');
					}, 2000);

					// Show success message.
					console.log('License key copied to clipboard');

				} catch (err) {
					console.error('Failed to copy license key: ', err);
				}

				// Remove temporary textarea.
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
				/* translators: 1: Number of sites activated, 2: Total sites allowed */
				__( '%1$s of %2$s sites have been activated with this license key', 'buddyboss' ),
				$license_info['total_prod_used'],
				999 <= (int) $license_info['total_prod_allowed'] ? 'unlimited' : $license_info['total_prod_allowed']
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
	 * Clear license details cache.
	 * Called when license status changes or plugin ID changes.
	 *
	 * @return void
	 */
	public static function clearLicenseDetailsCache(): void {
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId;
		$cache_key = $plugin_id . '_license_details';
		delete_transient( $cache_key );
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
		set_transient( $cache_key, $license_data, 12 * HOUR_IN_SECONDS );

		return $license_data;
	}

	/**
	 * AJAX handler for getting free license key.
	 *
	 * @return void
	 */
	public static function ajax_get_free_license(): void {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_get_free_license' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'buddyboss' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'buddyboss' ) );
		}

		// Get form data.
		$first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
		$last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
		$email      = sanitize_email( $_POST['email'] ?? '' );

		// Validate required fields.
		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
			wp_send_json_error( __( 'All fields are required', 'buddyboss' ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address', 'buddyboss' ) );
		}

		// Prepare API request data.
		$api_data = array(
			'email'      => $email,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);

		// Make API request.
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
					/* translators: %s is the error message */
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
					/* translators: %d is the HTTP status code */
					__( 'API returned error code: %d', 'buddyboss' ),
					$response_code
				)
			);
		}

		$data = json_decode( $response_body, true );

		if ( ! $data ) {
			wp_send_json_error( __( 'Invalid response from API', 'buddyboss' ) );
		}

		// Check if API returned success.
		if ( isset( $data['success'] ) && $data['success'] ) {
			$message     = isset( $data['message'] ) ? $data['message'] : __( 'License key generated successfully!', 'buddyboss' );
			$license_key = isset( $data['license_key'] ) ? $data['license_key'] : '';

			wp_send_json_success(
				array(
					'message'     => $message,
					'license_key' => $license_key,
				)
			);
		} else {
			$error_message = isset( $data['message'] ) ? $data['message'] : __( 'Failed to generate license key', 'buddyboss' );
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * AJAX handler for resetting license settings.
	 * Clears all license-related data including orphaned dynamic plugin ID.
	 *
	 * @return void
	 */
	public static function ajax_reset_license_settings(): void {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_reset_license_settings' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'buddyboss' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'buddyboss' ) );
		}

		try {
			$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

			// Get current plugin ID before clearing.
			$current_plugin_id = $pluginConnector->getCurrentPluginId();

			// Clear dynamic plugin ID.
			$pluginConnector->clearDynamicPluginId();

			// Clear license key.
			$pluginConnector->updateLicenseKey( '' );

			// Clear license activation status.
			$pluginConnector->updateLicenseActivationStatus( false );

			// Clear migration flag.
			delete_option( 'bb_mothership_licenses_migrated' );
			delete_site_option( 'bb_mothership_licenses_migrated' );

			// Clear all license-related transients.
			delete_transient( $current_plugin_id . '-mosh-products' );
			delete_transient( $current_plugin_id . '-mosh-addons-update-check' );
			delete_transient( $current_plugin_id . '_license_details' );

			// Also clear transients for the default plugin ID.
			delete_transient( PLATFORM_EDITION . '-mosh-products' );
			delete_transient( PLATFORM_EDITION . '-mosh-addons-update-check' );
			delete_transient( PLATFORM_EDITION . '_license_details' );

			// Clear rate limit and backoff data.
			self::delete_rate_limit_transient( 'buddyboss_license_rate_limit' );
			self::delete_rate_limit_transient( 'buddyboss_license_failed_attempts' );

			// Log the reset action.
			bb_error_log( 'License settings reset by user', true );

			wp_send_json_success(
				array(
					'message' => __( 'License settings have been reset successfully. You can now activate your license with the correct license key.', 'buddyboss' ),
				)
			);
		} catch ( \Exception $e ) {
			bb_error_log( sprintf( 'Error resetting license: %s', $e->getMessage() ), true );
			wp_send_json_error(
				sprintf(
					/* translators: %s is the error message */
					__( 'Failed to reset license settings: %s', 'buddyboss' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Check if we're currently being rate limited.
	 * Looks at stored rate limit data from previous requests.
	 *
	 * @return true|WP_Error True if OK to proceed, WP_Error if rate limited
	 */
	private static function checkRateLimit() {
		$rateLimitData = self::get_rate_limit_transient( 'buddyboss_license_rate_limit' );

		if ( ! $rateLimitData || ! is_array( $rateLimitData ) ) {
			return true; // No rate limit data, proceed.
		}

		$remaining   = isset( $rateLimitData['remaining'] ) ? (int) $rateLimitData['remaining'] : null;
		$resetTime   = isset( $rateLimitData['reset'] ) ? (int) $rateLimitData['reset'] : 0;
		$currentTime = time();
		$source      = isset( $rateLimitData['source'] ) ? $rateLimitData['source'] : 'unknown';

		bb_error_log(
			sprintf(
				'BuddyBoss: Checking rate limit - Source: %s, Remaining: %d, Reset at: %s (Unix: %d), Current: %s (Unix: %d)',
				$source,
				$remaining,
				date( 'Y-m-d H:i:s', $resetTime ),
				$resetTime,
				date( 'Y-m-d H:i:s', $currentTime ),
				$currentTime
			)
		);

		// If reset time has passed, clear the rate limit AND reset failed attempts.
		if ( $resetTime > 0 && $currentTime >= $resetTime ) {
			self::delete_rate_limit_transient( 'buddyboss_license_rate_limit' );
			self::delete_rate_limit_transient( 'buddyboss_license_failed_attempts' ); // Reset backoff counter.
			bb_error_log(
				sprintf(
					'BuddyBoss: Rate limit window EXPIRED - Reset time %s has passed. Cleared rate limit and failed attempts counter.',
					date( 'Y-m-d H:i:s', $resetTime )
				),
				true
			);
			return true;
		}

		// Check if we're currently blocked (either by remaining count OR reset time in future).
		if ( ( $remaining !== null && $remaining <= 0 ) || ( $resetTime > 0 && $currentTime < $resetTime ) ) {
			$waitTime    = max( 0, $resetTime - $currentTime );
			$waitMinutes = max( 1, ceil( $waitTime / 60 ) );

			bb_error_log(
				sprintf(
					'Rate limit exceeded - Wait %d minutes (reset: %s)',
					$waitMinutes,
					date( 'Y-m-d H:i:s', $resetTime )
				),
				true
			);

			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d is the number of minutes to wait */
					esc_html__( 'Rate limit exceeded. Please wait approximately %d minute(s) before trying again.', 'buddyboss' ),
					$waitMinutes
				)
			);
		}

		return true;
	}

	/**
	 * Store rate limit information from API response.
	 * This allows us to proactively check limits before making requests.
	 *
	 * @param array  $headers Response headers from the API.
	 * @param string $source  Source identifier (e.g., 'api_headers', 'validation_429').
	 *
	 * @return void
	 */
	private static function storeRateLimitInfo( $headers, $source = 'api_headers' ): void {
		if ( ! is_array( $headers ) ) {
			return;
		}

		// Extract rate limit headers (case-insensitive).
		$headers = array_change_key_case( $headers, CASE_LOWER );

		$limit     = isset( $headers['x-ratelimit-limit'] ) ? (int) $headers['x-ratelimit-limit'] : null;
		$remaining = isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : null;
		$reset     = isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : null;

		if ( null !== $remaining ) {
			$rateLimitData = array(
				'limit'     => $limit,
				'remaining' => $remaining,
				'reset'     => $reset,
				'source'    => $source,
				'timestamp' => time(),
			);

			// Store for up to 1 hour (rate limits typically reset within an hour).
			self::set_rate_limit_transient( 'buddyboss_license_rate_limit', $rateLimitData, HOUR_IN_SECONDS );

			// Rate limit info stored.
		}
	}

	/**
	 * Check if plugin is network activated (multisite).
	 *
	 * @return bool True if network activated, false otherwise.
	 */
	private static function isNetworkActivated(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( buddypress()->basename );
	}

	/**
	 * Get rate limit transient (multisite-aware).
	 *
	 * @param string $key Transient key.
	 * @return mixed Transient value or false.
	 */
	private static function get_rate_limit_transient( string $key ) {
		if ( self::isNetworkActivated() ) {
			return get_site_transient( $key );
		}
		return get_transient( $key );
	}

	/**
	 * Set rate limit transient (multisite-aware).
	 *
	 * @param string $key Transient key.
	 * @param mixed  $value Transient value.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	private static function set_rate_limit_transient( string $key, $value, int $expiration ) {
		if ( self::isNetworkActivated() ) {
			return set_site_transient( $key, $value, $expiration );
		}
		return set_transient( $key, $value, $expiration );
	}

	/**
	 * Delete rate limit transient (multisite-aware).
	 *
	 * @param string $key Transient key.
	 * @return bool True on success, false on failure.
	 */
	private static function delete_rate_limit_transient( string $key ) {
		if ( self::isNetworkActivated() ) {
			return delete_site_transient( $key );
		}
		return delete_transient( $key );
	}

	/**
	 * Track failed activation attempt for exponential backoff.
	 *
	 * @return void
	 */
	private static function trackFailedActivation(): void {
		$attempts = self::get_rate_limit_transient( 'buddyboss_license_failed_attempts' );
		$attempts = $attempts ? (int) $attempts : 0;
		++$attempts;

		// Store for 1 hour.
		self::set_rate_limit_transient( 'buddyboss_license_failed_attempts', $attempts, HOUR_IN_SECONDS );

		// Failed attempt tracked: attempt #$attempts.
	}

	/**
	 * Get suggested wait time based on exponential backoff.
	 *
	 * @return int Seconds to wait before retry
	 */
	private static function getBackoffWaitTime(): int {
		$attempts = self::get_rate_limit_transient( 'buddyboss_license_failed_attempts' );
		$attempts = $attempts ? (int) $attempts : 0;

		if ( $attempts === 0 ) {
			return 0;
		}

		// Exponential backoff: 2^attempts * base (30 seconds).
		// Attempt 1: 30s, Attempt 2: 60s, Attempt 3: 120s, Attempt 4: 240s, etc.
		// Max 15 minutes.
		$baseSeconds = 30;
		$waitTime    = min( pow( 2, $attempts - 1 ) * $baseSeconds, 900 );

		return (int) $waitTime;
	}

	/**
	 * Reset failed activation attempts counter.
	 *
	 * @return void
	 */
	private static function resetFailedAttempts(): void {
		self::delete_rate_limit_transient( 'buddyboss_license_failed_attempts' );
	}
}
