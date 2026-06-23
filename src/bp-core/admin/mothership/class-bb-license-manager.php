<?php
/**
 * BuddyBoss Platform - License Manager
 *
 * Extends the GroundLevel LicenseManager to add dynamic plugin ID functionality
 * and advanced rate limiting with exponential backoff.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since   BuddyBoss 2.14.0
 */

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
	 * Flag to control when the HTTP filter is active.
	 * Only set to true during actual license operations to minimize performance impact.
	 *
	 * @var bool
	 */
	private static $capture_headers_enabled = false;

	/**
	 * Enable HTTP header capture for the next license operation.
	 * Call this before making license API calls.
	 *
	 * @return void
	 */
	private static function enable_header_capture(): void {
		if ( ! self::$capture_headers_enabled ) {
			self::$capture_headers_enabled = true;
			add_filter( 'http_response', array( __CLASS__, 'capture_api_headers' ), 10, 3 );
		}
	}

	/**
	 * Disable HTTP header capture after license operation completes.
	 * Call this after making license API calls.
	 *
	 * @return void
	 */
	private static function disable_header_capture(): void {
		if ( self::$capture_headers_enabled ) {
			self::$capture_headers_enabled = false;
			remove_filter( 'http_response', array( __CLASS__, 'capture_api_headers' ), 10 );
		}
	}

	/**
	 * Get the API base URL for license operations.
	 * Environment-aware with multiple fallback options.
	 *
	 * @param string $plugin_id The plugin ID for product-specific constants.
	 *
	 * @return string The API base URL.
	 */
	private static function get_api_base_url( string $plugin_id = '' ): string {
		// Priority 1: Product-specific constant (e.g., BB_PLATFORM_MOTHERSHIP_API_BASE_URL).
		if ( ! empty( $plugin_id ) ) {
			$constant_name = strtoupper( str_replace( '-', '_', $plugin_id ) . '_MOTHERSHIP_API_BASE_URL' );
			if ( defined( $constant_name ) ) {
				return constant( $constant_name );
			}
		}

		// Priority 2: Generic BuddyBoss constant.
		if ( defined( 'BUDDYBOSS_MOTHERSHIP_API_BASE_URL' ) ) {
			return BUDDYBOSS_MOTHERSHIP_API_BASE_URL;
		}

		// Priority 3: Environment variable (useful for Docker/containerized environments).
		$env_api_url = getenv( 'BUDDYBOSS_API_URL' );
		if ( false !== $env_api_url && ! empty( $env_api_url ) ) {
			return trailingslashit( $env_api_url );
		}

		// Priority 4: WordPress option (for runtime configuration).
		$option_api_url = get_option( 'buddyboss_api_base_url' );
		if ( ! empty( $option_api_url ) ) {
			return trailingslashit( $option_api_url );
		}

		// Priority 5: Filter (allows plugins/themes to override).
		$default_url  = 'https://licenses.caseproof.com/api/v1/';
		$filtered_url = apply_filters( 'buddyboss_mothership_api_base_url', $default_url, $plugin_id );

		return $filtered_url;
	}

	/**
	 * Initialize hooks to capture API response headers.
	 * Should be called early in the WordPress lifecycle.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Hook is now registered dynamically during license operations only.
		// This init method kept for backward compatibility but doesn't register global hooks.
	}

	/**
	 * Capture rate limit headers from Caseproof API responses.
	 * Only runs when explicitly enabled during license operations.
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
					'rate_limit',
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
						gmdate( 'Y-m-d H:i:s', $reset_timestamp )
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
			} elseif ( 429 === $status_code ) {
				// For 429 errors without reset time, use default 1 hour window.
				$final_reset_time = time() + HOUR_IN_SECONDS;
				$source           = 'default_429_window';
				bb_error_log( 'BuddyBoss: 429 error without reset time - using 1 hour default', true );
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
				'rate_limit',
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
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in activateLicense() and deactivateLicense() methods
		if ( isset( $_POST['buddyboss_platform_license_button'] ) ) {
			$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

			// Setup dynamic plugin ID if present in license key.
			if ( isset( $_POST['license_key'] ) ) {
				$license_key          = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
				$_POST['license_key'] = self::setup_dynamic_plugin_id( $license_key, $plugin_connector );
			}

			$plugin_id = $plugin_connector->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( 'activate' === $_POST['buddyboss_platform_license_button'] ) {
				try {
					$license_key       = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
					$activation_domain = isset( $_POST['activation_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['activation_domain'] ) ) : '';
					self::activateLicense( $license_key, $activation_domain );
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
			} elseif ( 'deactivate' === $_POST['buddyboss_platform_license_button'] ) {
				try {
					$license_key       = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
					$activation_domain = isset( $_POST['activation_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['activation_domain'] ) ) : '';
					self::deactivateLicense( $license_key, $activation_domain );
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
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Activate a license with dynamic plugin ID support.
	 *
	 * @param string $license_key The license key.
	 * @param string $domain     The domain to activate on.
	 *
	 * @throws \Exception If the activation fails.
	 * @return void
	 */
	public static function activateLicense( string $license_key, string $domain ): void {
		self::validate_activation_permissions();
		self::validate_activation_inputs( $license_key, $domain );

		$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$license_key      = self::setup_dynamic_plugin_id( $license_key, $plugin_connector );

		$validation_error = self::validate_product_before_activation( $license_key, $plugin_connector->pluginId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( is_wp_error( $validation_error ) ) {
			$plugin_connector->clearDynamicPluginId();
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- WP_Error::get_error_message() returns sanitized text
			throw new \Exception( $validation_error->get_error_message() );
		}

		$response = self::perform_license_activation_api_call( $plugin_connector->pluginId, $license_key, $domain ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( $response instanceof Response && $response->isError() ) {
			self::handle_activation_error( $response, $plugin_connector );
		}

		if ( $response instanceof Response && ! $response->isError() ) {
			self::process_successful_activation( $license_key, $plugin_connector, $domain );
		}

		self::disable_header_capture();
	}

	/**
	 * Validate user permissions for license activation.
	 *
	 * @throws \Exception If validation fails.
	 * @return void
	 */
	private static function validate_activation_permissions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( esc_html__( 'You do not have permission to activate a license', 'buddyboss' ) );
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mothership_activate_license' ) ) {
			throw new \Exception( esc_html__( 'Invalid nonce', 'buddyboss' ) );
		}
	}

	/**
	 * Validate activation inputs and check rate limits.
	 *
	 * @param string $license_key The license key.
	 * @param string $domain     The activation domain.
	 *
	 * @throws \Exception If validation fails.
	 * @return void
	 */
	private static function validate_activation_inputs( string $license_key, string $domain ): void {
		if ( empty( $license_key ) ) {
			throw new \Exception( esc_html__( 'License key is required', 'buddyboss' ) );
		}

		if ( empty( $domain ) ) {
			throw new \Exception( esc_html__( 'Activation domain is required', 'buddyboss' ) );
		}

		$rate_limit_check = self::check_rate_limit();
		if ( is_wp_error( $rate_limit_check ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- WP_Error::get_error_message() returns sanitized text
			throw new \Exception( $rate_limit_check->get_error_message() );
		}
	}

	/**
	 * Perform the license activation API call.
	 *
	 * @param string $product    The product ID.
	 * @param string $license_key The license key.
	 * @param string $domain     The activation domain.
	 *
	 * @throws \Exception If API call fails.
	 * @return Response The API response.
	 */
	private static function perform_license_activation_api_call( string $product, string $license_key, string $domain ): Response {
		self::enable_header_capture();

		try {
			bb_error_log(
				sprintf(
					'BuddyBoss: Attempting license activation - Product: %s, Domain: %s',
					$product,
					$domain
				),
				true
			);

			return LicenseActivations::activate( $product, $license_key, $domain );
		} catch ( \Exception $e ) {
			self::disable_header_capture();
			bb_error_log( sprintf( 'License activation API exception: %s', $e->getMessage() ), true );
			throw new \Exception(
				esc_html__( 'License activation failed. Please check your license key and try again. If the problem persists, contact support.', 'buddyboss' )
			);
		}
	}

	/**
	 * Handle activation errors based on error code.
	 *
	 * @param Response                 $response        The API response.
	 * @param AbstractPluginConnection $plugin_connector The plugin connector.
	 *
	 * @throws \Exception Always throws exception with appropriate message.
	 * @return void
	 */
	private static function handle_activation_error( Response $response, $plugin_connector ): void {
		self::disable_header_capture();

		$error_code    = $response->__get( 'errorCode' );
		$error_message = $response->__get( 'error' );
		$errors        = $response->__get( 'errors' );

		self::track_failed_activation();

		bb_error_log(
			sprintf(
				'BuddyBoss: License activation failed - Code: %d, Message: %s, Product: %s',
				$error_code,
				$error_message,
				$plugin_connector->pluginId // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			),
			true
		);

		if ( 422 === $error_code ) {
			self::handle_activation_error_422( $errors, $plugin_connector );
		}

		if ( 429 === $error_code ) {
			self::handle_activation_error_429();
		}

		throw new \Exception(
			sprintf(
				/* translators: %s is the error message from API */
				esc_html__( 'License activation failed: %s', 'buddyboss' ),
				esc_html( $error_message )
			)
		);
	}

	/**
	 * Handle 422 product mismatch errors.
	 *
	 * @param array|null               $errors          The error details.
	 * @param AbstractPluginConnection $plugin_connector The plugin connector.
	 *
	 * @throws \Exception If product mismatch detected.
	 * @return void
	 */
	private static function handle_activation_error_422( $errors, $plugin_connector ): void {
		if ( is_array( $errors ) && isset( $errors['product'] ) ) {
			$plugin_connector->clearDynamicPluginId();
			bb_error_log( 'Cleared orphaned plugin ID (422)', true );

			throw new \Exception(
				esc_html__( 'License activation failed: The stored product ID did not match your license. Please try activating again with your license key.', 'buddyboss' )
			);
		}
	}

	/**
	 * Handle 429 rate limit errors with exponential backoff.
	 *
	 * @throws \Exception Always throws exception with wait time message.
	 * @return void
	 */
	private static function handle_activation_error_429(): void {
		$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );

		if ( $rate_limit_data && isset( $rate_limit_data['reset'] ) && $rate_limit_data['reset'] > 0 ) {
			$reset_time   = $rate_limit_data['reset'];
			$backoff_time = max( 0, $reset_time - time() );
			$wait_minutes = ceil( $backoff_time / 60 );
			bb_error_log( 'Using API reset time', true );
		} else {
			$backoff_time = self::get_backoff_wait_time();
			$reset_time   = time() + $backoff_time;
			$wait_minutes = ceil( $backoff_time / 60 );
			bb_error_log( 'Using calculated backoff time', true );
		}

		bb_error_log(
			sprintf(
				'Wait %d minutes (reset: %s)',
				$wait_minutes,
				gmdate( 'Y-m-d H:i:s', $reset_time )
			),
			true
		);

		$rate_limit_data = array(
			'limit'     => 10,
			'remaining' => 0,
			'reset'     => $reset_time,
			'timestamp' => time(),
			'source'    => 'calculated_backoff',
		);
		self::set_rate_limit_transient( 'rate_limit', $rate_limit_data, HOUR_IN_SECONDS );

		throw new \Exception(
			sprintf(
				/* translators: %d is the number of minutes to wait */
				esc_html__( 'License activation failed: Too many activation requests. Please wait approximately %d minute(s) before trying again.', 'buddyboss' ),
				max( 1, $wait_minutes ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- max() returns integer
			)
		);
	}

	/**
	 * Process successful license activation.
	 *
	 * @param string                   $license_key      The license key.
	 * @param AbstractPluginConnection $plugin_connector The plugin connector.
	 * @param string                   $domain          The activation domain.
	 *
	 * @throws \Exception If storing credentials fails.
	 * @return void
	 */
	private static function process_successful_activation( string $license_key, $plugin_connector, string $domain ): void {
		try {
			Credentials::storeLicenseKey( $license_key );
			$plugin_connector->updateLicenseActivationStatus( true );

			$plugin_id = $plugin_connector->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			delete_transient( $plugin_id . '-mosh-products' );
			delete_transient( $plugin_id . '-mosh-addons-update-check' );

			self::reset_failed_attempts();

			bb_error_log(
				sprintf(
					'BuddyBoss: License activated successfully - Product: %s, Domain: %s',
					$plugin_id,
					$domain
				),
				true
			);
		} catch ( \Exception $e ) {
			self::disable_header_capture();
			bb_error_log( sprintf( 'Error storing license credentials: %s', $e->getMessage() ), true );
			throw new \Exception( esc_html__( 'License activation succeeded but failed to save. Please try again.', 'buddyboss' ) );
		}
	}

	/**
	 * Validate that the product ID matches the license before activation.
	 * Prevents activation failures due to product mismatch.
	 *
	 * @param string $license_key The license key to validate.
	 * @param string $product_id  The product ID we're attempting to activate.
	 *
	 * @return true|WP_Error True if validation passes, WP_Error if fails.
	 */
	private static function validate_product_before_activation( string $license_key, string $product_id ) {
		// Skip validation if license key is empty (will be caught by input validation).
		if ( empty( $license_key ) ) {
			return true;
		}

		// Skip validation if we have any recent failed attempts (likely rate limited).
		// This preserves API calls for the actual activation attempt.
		$failed_attempts = self::get_rate_limit_transient( 'failed_attempts' );
		if ( $failed_attempts && (int) $failed_attempts > 0 ) {
			bb_error_log(
				sprintf(
					'BuddyBoss: Skipping pre-activation validation - %d recent failed attempts, preserving API calls',
					$failed_attempts
				)
			);
			return true;
		}

		// Also skip if we're currently rate limited (don't waste an API call).
		$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );
		if ( $rate_limit_data && is_array( $rate_limit_data ) ) {
			$reset_time   = isset( $rate_limit_data['reset'] ) ? (int) $rate_limit_data['reset'] : 0;
			$current_time = time();

			// Check if we're currently in a rate limit block (reset time is in the future).
			if ( $reset_time > 0 && $current_time < $reset_time ) {
				$wait_minutes = ceil( max( 0, $reset_time - $current_time ) / 60 );
				bb_error_log(
					sprintf(
						'Skipping pre-validation - rate limited until %s (%d minutes)',
						gmdate( 'Y-m-d H:i:s', $reset_time ),
						$wait_minutes
					),
					true
				);
				return true;
			}

			// Also check remaining count if available.
			$remaining = isset( $rate_limit_data['remaining'] ) ? (int) $rate_limit_data['remaining'] : null;
			if ( null !== $remaining && $remaining <= 1 ) {
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
			$response = \BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Licenses::get( $license_key );

			if ( $response instanceof Response && $response->isError() ) {
				$error_code = $response->__get( 'errorCode' );

				// If validation gets a 429, we're rate limited - store this info and block activation.
				if ( 429 === $error_code ) {
					bb_error_log( 'Validation encountered rate limit - blocking activation', true );

					// Track this as a failed attempt.
					self::track_failed_activation();

					// Check if HTTP filter already captured the ACTUAL reset time from API headers.
					$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );

					// Use actual reset time from captured headers if available, otherwise use exponential backoff.
					if ( $rate_limit_data && isset( $rate_limit_data['reset'] ) && $rate_limit_data['reset'] > 0 ) {
						$reset_time   = $rate_limit_data['reset'];
						$backoff_time = max( 0, $reset_time - time() );
						$source       = isset( $rate_limit_data['source'] ) ? $rate_limit_data['source'] : 'unknown';

						bb_error_log( 'Using API reset time', true );
					} else {
						$backoff_time = self::get_backoff_wait_time();
						$reset_time   = time() + $backoff_time;

						bb_error_log( 'Using calculated backoff time', true );
					}

					$wait_minutes = (int) ceil( $backoff_time / 60 );

					bb_error_log(
						sprintf(
							'Rate limit detected during validation - wait time: %d seconds (%d minutes)',
							$backoff_time,
							$wait_minutes
						),
						true
					);

					bb_error_log(
						sprintf(
							'Reset time: %s (Unix: %d)',
							gmdate( 'Y-m-d H:i:s', $reset_time ),
							$reset_time
						),
						true
					);

					// Store/update rate limit data (don't overwrite if we have actual API data).
					if ( ! $rate_limit_data || ! isset( $rate_limit_data['reset'] ) ) {
						set_transient(
							'rate_limit',
							array(
								'remaining' => 0,
								'reset'     => $reset_time,
								'source'    => 'validation_calculated',
							),
							$backoff_time + 60 // Add 60 seconds buffer.
						);
					}

					return new \WP_Error(
						'rate_limit',
						sprintf(
							/* translators: %d is the number of minutes to wait */
							esc_html__( 'Too many activation requests. Please wait approximately %d minute(s) before trying again.', 'buddyboss' ),
							max( 1, $wait_minutes )
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
				$license_data = $response->toArray();

				// Check if product field exists.
				if ( isset( $license_data['product'] ) ) {
					$actual_product = $license_data['product'];

					bb_error_log(
						sprintf(
							'BuddyBoss: License product validation - Expected: %s, Actual: %s',
							$product_id,
							$actual_product
						)
					);

					// If product doesn't match, this is likely an orphaned plugin ID.
					if ( $actual_product !== $product_id ) {
						bb_error_log(
							sprintf(
								'BuddyBoss: Product mismatch detected before activation - clearing orphaned plugin ID'
							),
							true
						);

						// Get plugin connector to clear the orphaned ID.
						$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
						$plugin_connector->clearDynamicPluginId();

						return new \WP_Error(
							'product_mismatch',
							sprintf(
								/* translators: 1: Expected product, 2: Actual product from license */
								esc_html__( 'Product validation failed: Your license is for "%2$s" but the system was configured for "%1$s". The configuration has been reset. Please try activating again.', 'buddyboss' ),
								$product_id,
								$actual_product
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
	 * @param string $license_key     The license key that may contain plugin ID.
	 * @param object $plugin_connector The plugin connector instance.
	 *
	 * @throws \Exception If plugin ID format is invalid.
	 * @return string The cleaned license key without plugin ID.
	 */
	private static function setup_dynamic_plugin_id( string $license_key, $plugin_connector ): string {
		$key_parts = explode( ':', $license_key );

		// Check if license key contains plugin ID in format KEY:PLUGIN_ID.
		if ( count( $key_parts ) === 2 && preg_match( '/^bb-/', $key_parts[1] ) ) {
			$plugin_id = $key_parts[1];

			// Validate plugin ID format for security.
			// Requirements: 3-50 chars, lowercase letters/numbers/hyphens, must start with letter,
			// no consecutive hyphens, must be buddyboss-related product.
			if ( strlen( $plugin_id ) < 3 || strlen( $plugin_id ) > 50 ) {
				throw new \Exception( esc_html__( 'Invalid plugin ID length in license key', 'buddyboss' ) );
			}

			// Must start with letter, contain only lowercase letters, numbers, single hyphens.
			if ( ! preg_match( '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $plugin_id ) ) {
				throw new \Exception( esc_html__( 'Invalid plugin ID format in license key', 'buddyboss' ) );
			}

			// Whitelist: Must be a known BuddyBoss product ID pattern.
			$allowed_prefixes = array( 'bb-platform', 'bb-web', 'buddyboss' );
			$is_valid_prefix  = false;
			foreach ( $allowed_prefixes as $prefix ) {
				if ( strpos( $plugin_id, $prefix ) === 0 ) {
					$is_valid_prefix = true;
					break;
				}
			}

			if ( ! $is_valid_prefix ) {
				throw new \Exception( esc_html__( 'Invalid product identifier in license key', 'buddyboss' ) );
			}

			// Store the web plugin ID.
			update_option( 'buddyboss_web_plugin_id', $plugin_id );

			// Set the dynamic plugin ID.
			$plugin_connector->setDynamicPluginId( $plugin_id );

			// Return the actual license key part.
			return $key_parts[0];
		}

		return $license_key;
	}

	/**
	 * Generates the HTML for the activation form.
	 * Overrides parent to use BuddyBoss specific button naming.
	 *
	 * @return string The HTML for the activation form.
	 */
	public function generateActivationForm(): string {
		ob_start();
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		?>
		<h2><?php esc_html_e( 'License Activation', 'buddyboss' ); ?></h2>
		<form method="post" action="" name="<?php echo esc_attr( $plugin_id ); ?>_activate_license_form">
			<div class="<?php echo esc_attr( $plugin_id ); ?>-license-form license-form-wrap">
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
										'<a href="#" id="get-free-license-link" rel="noopener noreferrer">' . esc_html__( 'here', 'buddyboss' ) . '</a>'
									);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<td colspan="2" scope="row">
							<?php wp_nonce_field( 'mothership_activate_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="activate">
							<input type="submit" value="<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>" class="button button-primary <?php echo esc_attr( $plugin_id ); ?>-button-activate">
						</td>
					</tr>
				</table>
			</div>
		</form>

		<?php
		echo $this->render_free_license_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns safe HTML
		echo $this->render_free_license_java_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns safe JavaScript
		return ob_get_clean();
	}

	/**
	 * Render the free license modal HTML.
	 *
	 * @return string The modal HTML.
	 */
	private function render_free_license_modal(): string {
		ob_start();
		?>
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
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the JavaScript for the free license modal.
	 *
	 * @return string The JavaScript code.
	 */
	private function render_free_license_java_script(): string {
		ob_start();
		?>
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
					nonce: '<?php echo esc_js( wp_create_nonce( 'bb_get_free_license' ) ); ?>'
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
		$plugin_id    = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$license_key  = Credentials::getLicenseKey();
		$license_info = $this->bb_get_license_details( $license_key );
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

		<form method="post" action="" name="<?php echo esc_attr( $plugin_id ); ?>_deactivate_license_form">
			<div class="<?php echo esc_attr( $plugin_id ); ?>-license-form license-form-wrap">
				<table class="form-table">
					<tr>
						<td colspan="2" scope="row">
							<input type="hidden" name="license_key" id="license_key" placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss' ); ?>" value="<?php echo esc_attr( $license_key ); ?>" readonly />
							<input type="hidden" name="activation_domain" id="activation_domain" value="<?php echo esc_attr( Credentials::getActivationDomain() ); ?>" />
							<?php wp_nonce_field( 'mothership_deactivate_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="deactivate">
							<input type="submit" value="<?php esc_html_e( 'Deactivate License', 'buddyboss' ); ?>" class="button button-secondary <?php echo esc_attr( $plugin_id ); ?>-button-deactivate" >
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
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
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
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

		// Create cache key based on plugin ID only (not license key for security).
		$cache_key = $plugin_id . '_license_details';

		// Check cache first unless force refresh is requested.
		if ( ! $force_refresh ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data && ! is_wp_error( $cached_data ) ) {
				return $cached_data;
			}
		}

		$root_api_url = self::get_api_base_url( $plugin_id );

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
		// Verify nonce - check existence first to prevent PHP warnings.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_get_free_license' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'buddyboss' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'buddyboss' ) );
		}

		// Get form data.
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

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
		// Verify nonce - check existence first to prevent PHP warnings.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_reset_license_settings' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'buddyboss' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'buddyboss' ) );
		}

		try {
			$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

			// Get current plugin ID before clearing.
			$current_plugin_id = $plugin_connector->getCurrentPluginId();

			// Clear dynamic plugin ID.
			$plugin_connector->clearDynamicPluginId();

			// Clear web plugin ID (set when using KEY:PLUGIN_ID format).
			delete_option( 'buddyboss_web_plugin_id' );

			// Clear license key.
			$plugin_connector->updateLicenseKey( '' );

			// Clear license activation status.
			$plugin_connector->updateLicenseActivationStatus( false );

			// Clear migration flag.
			delete_option( 'bb_mothership_licenses_migrated' );
			delete_site_option( 'bb_mothership_licenses_migrated' );

			// List all possible plugin IDs that might have stored data.
			$all_plugin_ids = array(
				$current_plugin_id,
				PLATFORM_EDITION,
				'bb-platform-pro-1-site',
				'bb-platform-pro-2-sites',
				'bb-platform-pro-5-sites',
				'bb-platform-pro-10-sites',
				'bb-platform-free',
				'bb-web',
				'bb-web-2-sites',
				'bb-web-5-sites',
				'bb-web-10-sites',
				'bb-web-20-sites',
			);

			// Clear all license-related data for all possible plugin IDs.
			foreach ( $all_plugin_ids as $plugin_id ) {
				// Clear license keys and activation status.
				delete_option( $plugin_id . '_license_key' );
				delete_option( $plugin_id . '_license_activation_status' );

				// Clear transients (both regular and site-wide for multisite).
				delete_transient( $plugin_id . '-mosh-products' );
				delete_transient( $plugin_id . '-mosh-addons-update-check' );
				delete_transient( $plugin_id . '_license_details' );
				delete_site_transient( $plugin_id . '-mosh-products' );
				delete_site_transient( $plugin_id . '-mosh-addons-update-check' );
				delete_site_transient( $plugin_id . '_license_details' );
			}

			// Clear rate limit and backoff data.
			self::delete_rate_limit_transient( 'rate_limit' );
			self::delete_rate_limit_transient( 'failed_attempts' );

			// Log the reset action.
			bb_error_log( 'License settings reset by user - all mothership data cleared', true );

			wp_send_json_success(
				array(
					'message' => __( 'License settings have been reset successfully. You can now activate your license with the correct license key.', 'buddyboss' ),
				)
			);
		} catch ( \Exception $e ) {
			bb_error_log( sprintf( 'Error resetting license: %s', $e->getMessage() ), true );
			// Don't expose internal errors to users via AJAX response.
			wp_send_json_error(
				__( 'Failed to reset license settings. Please try again or contact support if the problem persists.', 'buddyboss' )
			);
		}
	}

	/**
	 * Check if we're currently being rate limited.
	 * Looks at stored rate limit data from previous requests.
	 *
	 * @return true|WP_Error True if OK to proceed, WP_Error if rate limited
	 */
	private static function check_rate_limit() {
		$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );

		if ( ! $rate_limit_data || ! is_array( $rate_limit_data ) ) {
			return true; // No rate limit data, proceed.
		}

		$remaining    = isset( $rate_limit_data['remaining'] ) ? (int) $rate_limit_data['remaining'] : null;
		$reset_time   = isset( $rate_limit_data['reset'] ) ? (int) $rate_limit_data['reset'] : 0;
		$current_time = time();
		$source       = isset( $rate_limit_data['source'] ) ? $rate_limit_data['source'] : 'unknown';

		// Check for invalid reset time (0 or very old timestamp).
		// Unix epoch (0) or timestamps more than 1 year old indicate corrupted data.
		if ( $reset_time <= 0 || $reset_time < ( $current_time - YEAR_IN_SECONDS ) ) {
			bb_error_log(
				sprintf(
					'BuddyBoss: Invalid rate limit data detected (reset: %d) - clearing corrupted data',
					$reset_time
				),
				true
			);
			self::delete_rate_limit_transient( 'rate_limit' );
			return true; // Clear invalid data and proceed.
		}

		bb_error_log(
			sprintf(
				'BuddyBoss: Checking rate limit - Source: %s, Remaining: %d, Reset at: %s (Unix: %d), Current: %s (Unix: %d)',
				$source,
				$remaining,
				gmdate( 'Y-m-d H:i:s', $reset_time ),
				$reset_time,
				gmdate( 'Y-m-d H:i:s', $current_time ),
				$current_time
			)
		);

		// If reset time has passed, clear the rate limit AND reset failed attempts.
		if ( $current_time >= $reset_time ) {
			self::delete_rate_limit_transient( 'rate_limit' );
			self::delete_rate_limit_transient( 'failed_attempts' ); // Reset backoff counter.
			bb_error_log(
				sprintf(
					'BuddyBoss: Rate limit window EXPIRED - Reset time %s has passed. Cleared rate limit and failed attempts counter.',
					gmdate( 'Y-m-d H:i:s', $reset_time )
				),
				true
			);
			return true;
		}

		// Check if we're currently blocked by remaining count.
		// Note: We already know currentTime < resetTime (otherwise we would have returned above).
		if ( null !== $remaining && $remaining <= 0 ) {
			$wait_time    = max( 0, $reset_time - $current_time );
			$wait_minutes = max( 1, ceil( $wait_time / 60 ) );

			bb_error_log(
				sprintf(
					'Rate limit exceeded - Wait %d minutes (reset: %s)',
					$wait_minutes,
					gmdate( 'Y-m-d H:i:s', $reset_time )
				),
				true
			);

			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d is the number of minutes to wait */
					esc_html__( 'Rate limit exceeded. Please wait approximately %d minute(s) before trying again.', 'buddyboss' ),
					$wait_minutes
				)
			);
		}

		return true;
	}

	/**
	 * Check if plugin is network activated (multisite).
	 *
	 * @return bool True if network activated, false otherwise.
	 */
	private static function is_network_activated(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( buddypress()->basename );
	}

	/**
	 * Get rate limit transient (multisite-aware) with collision prevention.
	 *
	 * @param string $key Transient key.
	 * @return mixed Transient value or false.
	 */
	private static function get_rate_limit_transient( string $key ) {
		// Add prefix to prevent collisions with other plugins.
		$prefixed_key = 'bb_license_' . $key;

		if ( self::is_network_activated() ) {
			return get_site_transient( $prefixed_key );
		}
		return get_transient( $prefixed_key );
	}

	/**
	 * Set rate limit transient (multisite-aware) with size validation and collision prevention.
	 *
	 * @param string $key Transient key.
	 * @param mixed  $value Transient value.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	private static function set_rate_limit_transient( string $key, $value, int $expiration ) {
		// Add prefix to prevent collisions with other plugins.
		$prefixed_key = 'bb_license_' . $key;

		// Validate data size to prevent cache bloat (limit to 2KB).
		$serialized = maybe_serialize( $value );
		// Cast to string for PHP 8+ compatibility (maybe_serialize can return scalars).
		$serialized_string = is_string( $serialized ) ? $serialized : (string) $serialized;
		if ( strlen( $serialized_string ) > 2048 ) {
			bb_error_log( 'Rate limit data exceeds maximum size (2KB)', true );
			return false;
		}

		if ( self::is_network_activated() ) {
			return set_site_transient( $prefixed_key, $value, $expiration );
		}
		return set_transient( $prefixed_key, $value, $expiration );
	}

	/**
	 * Delete rate limit transient (multisite-aware) with collision prevention.
	 *
	 * @param string $key Transient key.
	 * @return bool True on success, false on failure.
	 */
	private static function delete_rate_limit_transient( string $key ) {
		// Add prefix to prevent collisions with other plugins.
		$prefixed_key = 'bb_license_' . $key;

		if ( self::is_network_activated() ) {
			return delete_site_transient( $prefixed_key );
		}
		return delete_transient( $prefixed_key );
	}

	/**
	 * Track failed activation attempt for exponential backoff.
	 *
	 * @return void
	 */
	private static function track_failed_activation(): void {
		$attempts = self::get_rate_limit_transient( 'failed_attempts' );
		$attempts = $attempts ? (int) $attempts : 0;
		++$attempts;

		// Store for 1 hour.
		self::set_rate_limit_transient( 'failed_attempts', $attempts, HOUR_IN_SECONDS );

		// Failed attempt tracked: attempt #$attempts.
	}

	/**
	 * Get suggested wait time based on exponential backoff.
	 *
	 * @return int Seconds to wait before retry
	 */
	private static function get_backoff_wait_time(): int {
		$attempts = self::get_rate_limit_transient( 'failed_attempts' );
		$attempts = $attempts ? (int) $attempts : 0;

		if ( 0 === $attempts ) {
			return 0;
		}

		// Exponential backoff: 2^attempts * base (30 seconds).
		// Attempt 1: 30s, Attempt 2: 60s, Attempt 3: 120s, Attempt 4: 240s, etc.
		// Max 15 minutes.
		$base_seconds = 30;
		$wait_time    = min( pow( 2, $attempts - 1 ) * $base_seconds, 900 );

		return (int) $wait_time;
	}

	/**
	 * Reset failed activation attempts counter.
	 *
	 * @return void
	 */
	private static function reset_failed_attempts(): void {
		self::delete_rate_limit_transient( 'failed_attempts' );
	}
}