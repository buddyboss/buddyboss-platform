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
	 * The controller for handling the license activation/deactivation/reset post requests.
	 * Overrides the parent controller to add dynamic plugin ID support and reset functionality.
	 *
	 * @return void
	 */
	public static function controller(): void {
		if ( ! isset( $_POST['buddyboss_platform_license_button'] ) ) {
			return;
		}

		$action          = $_POST['buddyboss_platform_license_button'];
		$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

		// Handle reset action.
		if ( $action === 'reset' ) {
			try {
				self::resetLicenseData();
				printf(
					'<div class="notice notice-success"><p>%s</p></div>',
					esc_html__( 'License settings have been reset successfully. You can now enter your license key to activate.', 'buddyboss' )
				);
			} catch ( \Exception $e ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( $e->getMessage() )
				);
			}
			return;
		}

		// Setup dynamic plugin ID if present in license key
		if ( isset( $_POST['license_key'] ) ) {
			$_POST['license_key'] = self::setupDynamicPluginId( $_POST['license_key'], $pluginConnector );
		}

		$pluginId = $pluginConnector->pluginId;

		// Handle activation.
		if ( $action === 'activate' ) {
			// Check rate limiting early.
			$rate_limit = self::checkRateLimit();
			if ( $rate_limit['blocked'] ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( $rate_limit['message'] )
				);
				return;
			}

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
		} elseif ( $action === 'deactivate' ) {
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

		// Check rate limiting BEFORE making API call.
		$rate_limit = self::checkRateLimit();
		if ( $rate_limit['blocked'] ) {
			throw new \Exception( $rate_limit['message'] );
		}

		$pluginConnector = self::getContainer()->get( AbstractPluginConnection::class );

		// Setup dynamic plugin ID if present in license key
		$licenseKey = self::setupDynamicPluginId( $licenseKey, $pluginConnector );

		// Hook to capture response headers for rate limit detection.
		add_filter( 'http_response', array( __CLASS__, 'captureResponseHeaders' ), 10, 5 );

		// Reset captured headers before making request.
		self::$last_response_headers = null;

		// Translators: %s is the response error message.
		$errorHtml = esc_html__( 'License activation failed: %s', 'buddyboss' );

		try {
			$product  = $pluginConnector->pluginId;
			$response = LicenseActivations::activate( $product, $licenseKey, $domain );
		} catch ( \Exception $e ) {
			// Remove the hook.
			remove_filter( 'http_response', array( __CLASS__, 'captureResponseHeaders' ), 10 );
			throw new \Exception(
				sprintf(
					$errorHtml,
					$e->getMessage()
				)
			);
		}

		error_log( print_r( $response, true ) . "\n", 3, WP_CONTENT_DIR . '/debug_chetan_new.log' );

		// Extract Retry-After header if present.
		$retryAfter = self::getRetryAfterFromHeaders();

		// Log complete API response for debugging.
		if ( $response instanceof Response ) {
			error_log( '==================== BuddyBoss License API Response ====================' );
			error_log( 'Response Status: ' . ( $response->isError() ? 'ERROR' : 'SUCCESS' ) );
			error_log( 'Error Code: ' . ( $response->errorCode ?? 'none' ) );
			error_log( 'Error Message: ' . print_r( $response->error, true ) );
			error_log( 'Error Details: ' . print_r( $response->errors, true ) );

			if ( null !== self::$last_response_headers ) {
				error_log( '--- Response Headers ---' );
				error_log( print_r( self::$last_response_headers, true ) );
				error_log( '--- Rate Limit Headers (normalized to lowercase) ---' );
				error_log( 'x-ratelimit-limit: ' . ( self::$last_response_headers['x-ratelimit-limit'] ?? 'not set' ) );
				error_log( 'x-ratelimit-remaining: ' . ( self::$last_response_headers['x-ratelimit-remaining'] ?? 'not set' ) );
				error_log( 'x-ratelimit-reset: ' . ( self::$last_response_headers['x-ratelimit-reset'] ?? 'not set' ) );
				error_log( 'retry-after: ' . ( is_array( self::$last_response_headers['retry-after'] ?? null ) ? print_r( self::$last_response_headers['retry-after'], true ) : ( self::$last_response_headers['retry-after'] ?? 'not set' ) ) );
			} else {
				error_log( '--- Response Headers: NOT CAPTURED ---' );
			}

			error_log( 'Extracted Retry-After Value: ' . ( $retryAfter ?? 'null' ) . ' seconds' );
			error_log( '========================================================================' );
		}

		// Remove the hook.
		remove_filter( 'http_response', array( __CLASS__, 'captureResponseHeaders' ), 10 );

		// Handle error response with intelligent recovery.
		if ( $response instanceof Response && $response->isError() ) {
			$errorMessage = self::handleActivationError( $response, $pluginConnector, $retryAfter );
			throw new \Exception( $errorMessage );
		}

		// Success - store credentials and update status.
		if ( $response instanceof Response && ! $response->isError() ) {
			try {
				Credentials::storeLicenseKey( $licenseKey );
				$pluginConnector->updateLicenseActivationStatus( true );

				// Clear all caches to force refresh.
				// Note: updateLicenseActivationStatus already clears license details and add-ons cache,
				// but we explicitly clear product transients here for consistency.
				$pluginId = $pluginConnector->pluginId;
				delete_transient( $pluginId . '-mosh-products' );
				delete_transient( $pluginId . '-mosh-addons-update-check' );

				// Clear rate limit on success.
				$network_activated = self::isNetworkActivated();
				if ( $network_activated ) {
					delete_site_transient( 'bb_license_activation_blocked' );
				} else {
					delete_transient( 'bb_license_activation_blocked' );
				}
			} catch ( \Exception $e ) {
				throw new \Exception( $e->getMessage() );
			}
		}
	}

	/**
	 * Temporary storage for captured response headers.
	 *
	 * @var array|null
	 */
	private static $last_response_headers = null;

	/**
	 * Hook into HTTP API to capture response headers.
	 *
	 * @param array  $response HTTP response.
	 * @param string $context  Context.
	 * @param string $class    HTTP transport class.
	 * @param array  $args     HTTP request arguments.
	 * @param string $url      Request URL.
	 *
	 * @return array Unmodified response.
	 */
	public static function captureResponseHeaders( $response, $context = '', $class = '', $args = array(), $url = '' ) {
		// Only capture headers for license API requests.
		if ( is_string( $url ) && false !== strpos( $url, 'licenses.caseproof.com' ) ) {
			error_log( 'BB License: captureResponseHeaders called for licenses.caseproof.com' );

			if ( is_array( $response ) && isset( $response['headers'] ) ) {
				$headers = $response['headers'];
				error_log( 'BB License: Headers object type: ' . get_class( $headers ) );

				// Extract specific headers we care about (works with both array and CaseInsensitiveDictionary).
				self::$last_response_headers = array(
					'retry-after'           => $headers['retry-after'] ?? $headers['Retry-After'] ?? null,
					'x-ratelimit-reset'     => $headers['x-ratelimit-reset'] ?? $headers['X-RateLimit-Reset'] ?? null,
					'x-ratelimit-limit'     => $headers['x-ratelimit-limit'] ?? $headers['X-RateLimit-Limit'] ?? null,
					'x-ratelimit-remaining' => $headers['x-ratelimit-remaining'] ?? $headers['X-RateLimit-Remaining'] ?? null,
				);

				error_log( 'BB License: Captured retry-after = ' . print_r( self::$last_response_headers['retry-after'], true ) );
			} else {
				error_log( 'BB License: Response array or headers missing' );
			}
		}
		return $response;
	}

	/**
	 * Extract rate limit wait time from captured headers.
	 *
	 * Checks for X-RateLimit-Reset, X-RateLimit-Remaining, and Retry-After headers.
	 *
	 * @return int|null Wait time in seconds, or null if not found.
	 */
	private static function getRetryAfterFromHeaders(): ?int {
		if ( null === self::$last_response_headers ) {
			return null;
		}

		$headers = self::$last_response_headers;

		// Priority 1: Check X-RateLimit-Reset header (Unix timestamp or seconds until reset).
		// Keys are normalized to lowercase in captureResponseHeaders.
		if ( isset( $headers['x-ratelimit-reset'] ) && ! empty( $headers['x-ratelimit-reset'] ) ) {
			$reset_value = $headers['x-ratelimit-reset'];

			// Header values can be arrays - extract first element.
			if ( is_array( $reset_value ) && ! empty( $reset_value ) ) {
				$reset_value = $reset_value[0];
			}

			// If it's a Unix timestamp (10 digits), calculate wait time.
			if ( is_numeric( $reset_value ) ) {
				$reset_value = (int) $reset_value;

				// Check if it's a Unix timestamp (large number) or seconds (small number).
				if ( $reset_value > 10000 ) {
					// Looks like a Unix timestamp.
					$wait_time = $reset_value - time();
					return max( 0, $wait_time );
				} else {
					// Looks like seconds until reset.
					return max( 0, $reset_value );
				}
			}
		}

		// Priority 2: Check Retry-After header (standard HTTP header).
		// Keys are normalized to lowercase in captureResponseHeaders.
		if ( isset( $headers['retry-after'] ) && ! empty( $headers['retry-after'] ) ) {
			$retry_after = $headers['retry-after'];

			// Header values can be arrays - extract first element.
			if ( is_array( $retry_after ) && ! empty( $retry_after ) ) {
				$retry_after = $retry_after[0];
			}

			// Retry-After can be either seconds (integer) or HTTP date.
			if ( is_numeric( $retry_after ) ) {
				return (int) $retry_after;
			} else {
				// Try to parse as HTTP date.
				$retry_time = strtotime( $retry_after );
				if ( false !== $retry_time ) {
					$wait_time = $retry_time - time();
					return max( 0, $wait_time );
				}
			}
		}

		return null;
	}

	/**
	 * Check if plugin is network activated.
	 *
	 * @return bool True if plugin is network activated, false otherwise.
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
	 * Check if activation is currently rate-limited.
	 *
	 * @return array ['blocked' => bool, 'retry_after' => int|null, 'message' => string]
	 */
	private static function checkRateLimit(): array {
		$network_activated = self::isNetworkActivated();

		// Check for rate limit transient
		$blocked_until = $network_activated
			? get_site_transient( 'bb_license_activation_blocked' )
			: get_transient( 'bb_license_activation_blocked' );

		if ( false === $blocked_until ) {
			return array(
				'blocked'     => false,
				'retry_after' => null,
				'message'     => '',
			);
		}

		$wait_seconds = $blocked_until - time();
		if ( $wait_seconds <= 0 ) {
			// Expired, clear the transient
			if ( $network_activated ) {
				delete_site_transient( 'bb_license_activation_blocked' );
			} else {
				delete_transient( 'bb_license_activation_blocked' );
			}
			return array(
				'blocked'     => false,
				'retry_after' => null,
				'message'     => '',
			);
		}

		$wait_minutes = ceil( $wait_seconds / 60 );
		return array(
			'blocked'     => true,
			'retry_after' => $wait_seconds,
			'message'     => sprintf(
				// Translators: %d is the number of minutes to wait.
				__( 'Too many activation attempts. Please wait %d minutes before trying again.', 'buddyboss' ),
				$wait_minutes
			),
		);
	}

	/**
	 * Handle specific error codes with appropriate recovery actions.
	 *
	 * @param Response                 $response        API error response.
	 * @param AbstractPluginConnection $pluginConnector Plugin connector instance.
	 * @param int|null                 $retryAfter      Optional retry-after time in seconds from response header.
	 *
	 * @return string User-friendly error message.
	 */
	private static function handleActivationError( Response $response, $pluginConnector, $retryAfter = null ): string {
		$errorCode    = $response->errorCode;
		$errorMessage = $response->error;
		$errors       = $response->errors;

		// Ensure error message is a string (sometimes it's just the error code).
		if ( ! is_string( $errorMessage ) ) {
			$errorMessage = (string) $errorMessage;
		}

		// Handle 422 Product Mismatch - Only clear if product field error exists.
		if ( 422 === $errorCode && isset( $errors['product'] ) ) {
			// Clear orphaned plugin ID.
			$pluginConnector->clearDynamicPluginId();

			// Clear migration flag to allow retry.
			if ( self::isNetworkActivated() ) {
				delete_site_option( 'bb_mothership_licenses_migrated' );
			}
			delete_option( 'bb_mothership_licenses_migrated' );

			// Log the auto-recovery action.
			error_log( sprintf(
				'BuddyBoss License: Auto-cleared dynamic plugin ID due to 422 product mismatch. Error: %s',
				$errorMessage
			) );

			return sprintf(
				// Translators: %s is the error message.
				__( 'License product mismatch detected. Your license settings have been reset. Please try activating again. Error: %s', 'buddyboss' ),
				$errorMessage
			);
		}

		// Handle 429 Rate Limiting.
		if ( 429 === $errorCode ) {
			// Priority 1: Use Retry-After header value if provided.
			if ( null !== $retryAfter && $retryAfter > 0 ) {
				$wait_time = (int) $retryAfter;
			} else {
				// Priority 2: Try to extract wait time from error message.
				// Cloudflare rate limits are typically much longer than API rate limits.
				// Use 30 minutes as default for Cloudflare blocks, 5 minutes for API rate limits.
				$is_cloudflare = is_string( $errorMessage ) && (
					strpos( $errorMessage, 'cloudflare' ) !== false ||
					strpos( $errorMessage, 'Cloudflare' ) !== false ||
					strpos( $errorMessage, '1015' ) !== false
				);

				$wait_time = $is_cloudflare ? 1800 : 300; // 30 min for Cloudflare, 5 min for API

				// Check if error message contains retry information.
				// Common formats: "Try again in X minutes", "Retry after X seconds", etc.
				if ( is_string( $errorMessage ) && preg_match( '/(\d+)\s*minute/i', $errorMessage, $matches ) ) {
					$wait_time = (int) $matches[1] * 60;
				} elseif ( is_string( $errorMessage ) && preg_match( '/(\d+)\s*second/i', $errorMessage, $matches ) ) {
					$wait_time = (int) $matches[1];
				} elseif ( is_string( $errorMessage ) && preg_match( '/(\d+)\s*hour/i', $errorMessage, $matches ) ) {
					$wait_time = (int) $matches[1] * 3600;
				}
			}

			// Cap wait time between 1 minute and 60 minutes for sanity.
			$wait_time = max( 60, min( 3600, $wait_time ) );

			// Allow filtering the wait time.
			$wait_time = apply_filters( 'bb_license_rate_limit_wait_time', $wait_time, $errorCode, $errorMessage );

			$network_activated = self::isNetworkActivated();

			if ( $network_activated ) {
				set_site_transient( 'bb_license_activation_blocked', time() + $wait_time, $wait_time );
			} else {
				set_transient( 'bb_license_activation_blocked', time() + $wait_time, $wait_time );
			}

			// Log the rate limit for debugging with header information.
			$source = 'default (5 minutes)';
			if ( $retryAfter ) {
				// Check which header was used.
				if ( null !== self::$last_response_headers ) {
					if ( isset( self::$last_response_headers['X-RateLimit-Reset'] ) || isset( self::$last_response_headers['x-ratelimit-reset'] ) ) {
						$source = 'X-RateLimit-Reset header';
					} elseif ( isset( self::$last_response_headers['Retry-After'] ) || isset( self::$last_response_headers['retry-after'] ) ) {
						$source = 'Retry-After header';
					}
				}
			} elseif ( preg_match( '/\d+\s*(minute|second|hour)/i', $errorMessage ) ) {
				$source = 'error message parsing';
			}

			error_log( sprintf(
				'BuddyBoss License: Rate limited for %d seconds (from %s). Headers: %s',
				$wait_time,
				$source,
				null !== self::$last_response_headers ? wp_json_encode( array(
					'X-RateLimit-Limit'     => self::$last_response_headers['X-RateLimit-Limit'] ?? self::$last_response_headers['x-ratelimit-limit'] ?? 'not set',
					'X-RateLimit-Remaining' => self::$last_response_headers['X-RateLimit-Remaining'] ?? self::$last_response_headers['x-ratelimit-remaining'] ?? 'not set',
					'X-RateLimit-Reset'     => self::$last_response_headers['X-RateLimit-Reset'] ?? self::$last_response_headers['x-ratelimit-reset'] ?? 'not set',
					'Retry-After'           => self::$last_response_headers['Retry-After'] ?? self::$last_response_headers['retry-after'] ?? 'not set',
				) ) : 'no headers captured'
			) );

			return sprintf(
				// Translators: 1: Wait time in minutes, 2: Error message.
				__( 'Too many activation attempts. Please wait %1$d minutes before trying again. Error: %2$s', 'buddyboss' ),
				ceil( $wait_time / 60 ),
				$errorMessage
			);
		}

		// Handle 401 Unauthorized.
		if ( 401 === $errorCode ) {
			return sprintf(
				// Translators: %s is the error message.
				__( 'License key is invalid or expired. Please check your license key. Error: %s', 'buddyboss' ),
				$errorMessage
			);
		}

		// Generic error.
		return sprintf(
			// Translators: 1: Error message, 2: Error code.
			__( 'License activation failed: %1$s (Error code: %2$d)', 'buddyboss' ),
			$errorMessage,
			$errorCode
		);
	}

	/**
	 * Reset all license-related data (handler for reset button).
	 *
	 * @throws \Exception If user lacks permissions or nonce fails.
	 * @return void
	 */
	public static function resetLicenseData(): void {
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( esc_html__( 'You do not have permission to reset license data', 'buddyboss' ) );
		}

		// Check nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'mothership_reset_license' ) ) {
			throw new \Exception( esc_html__( 'Invalid nonce', 'buddyboss' ) );
		}

		$pluginConnector   = self::getContainer()->get( AbstractPluginConnection::class );
		$pluginId          = $pluginConnector->pluginId;
		$network_activated = self::isNetworkActivated();

		// Log the reset action.
		error_log( sprintf(
			'BuddyBoss License Reset by user %d (%s) at %s',
			get_current_user_id(),
			wp_get_current_user()->user_email,
			current_time( 'mysql' )
		) );

		// Clear all license options.
		$options_to_clear = array(
			'buddyboss_dynamic_plugin_id',
			'buddyboss_web_plugin_id',
			$pluginId . '_license_key',
			$pluginId . '_license_activation_status',
			'bb_mothership_licenses_migrated',
		);

		// Also clear PLATFORM_EDITION options if different.
		if ( $pluginId !== PLATFORM_EDITION ) {
			$options_to_clear[] = PLATFORM_EDITION . '_license_key';
			$options_to_clear[] = PLATFORM_EDITION . '_license_activation_status';
		}

		foreach ( $options_to_clear as $option ) {
			if ( $network_activated ) {
				delete_site_option( $option );
			}
			delete_option( $option );
		}

		// Clear all transients.
		$transients_to_clear = array(
			$pluginId . '_license_details',
			$pluginId . '_add_ons',
			$pluginId . '-mosh-products',
			$pluginId . '-mosh-addons-update-check',
			'bb_license_activation_blocked',
		);

		// Also clear PLATFORM_EDITION transients if different.
		if ( $pluginId !== PLATFORM_EDITION ) {
			$transients_to_clear[] = PLATFORM_EDITION . '_license_details';
			$transients_to_clear[] = PLATFORM_EDITION . '_add_ons';
			$transients_to_clear[] = PLATFORM_EDITION . '-mosh-products';
			$transients_to_clear[] = PLATFORM_EDITION . '-mosh-addons-update-check';
		}

		foreach ( $transients_to_clear as $transient ) {
			delete_transient( $transient );
			if ( $network_activated ) {
				delete_site_transient( $transient );
			}
		}

		// Clear dynamic plugin ID using connector.
		$pluginConnector->clearDynamicPluginId();

		// Clear caches.
		self::clearLicenseDetailsCache();
		BB_Addons_Manager::clearProductAddOnsCache();
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

		// Check for rate limiting (but don't show notice).
		$rate_limit = self::checkRateLimit();
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
							<input type="submit"
								value="<?php esc_attr_e( 'Activate License', 'buddyboss' ); ?>"
								class="button button-primary <?php echo esc_attr( $pluginId ); ?>-button-activate"
								<?php echo $rate_limit['blocked'] ? 'disabled' : ''; ?>>
						</td>
					</tr>
				</table>
			</div>
		</form>

		<!-- Troubleshooting Section -->
		<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
			<h3><?php esc_html_e( 'Having trouble activating?', 'buddyboss' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'If you receive repeated errors or your license key isn\'t working, try resetting your license settings.', 'buddyboss' ); ?>
			</p>
			<form method="post" action="" name="<?php echo esc_attr( $pluginId ); ?>_reset_license_form_activation" style="margin-top: 10px;">
				<?php wp_nonce_field( 'mothership_reset_license', '_wpnonce' ); ?>
				<input type="hidden" name="buddyboss_platform_license_button" value="reset">
				<input type="submit"
					value="<?php esc_attr_e( 'Reset License Settings', 'buddyboss' ); ?>"
					class="button button-secondary"
					onclick="return confirm('<?php echo esc_js( __( 'This will clear all license data. Continue?', 'buddyboss' ) ); ?>');" />
			</form>
		</div>

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

		<!-- Deactivate Form -->
		<form method="post" action="" name="<?php echo esc_attr( $pluginId ); ?>_deactivate_license_form" style="margin-bottom: 20px;">
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

		<!-- Reset License Settings Section -->
		<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
			<h3><?php esc_html_e( 'Troubleshooting', 'buddyboss' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'If you are experiencing activation issues or errors, resetting your license settings may help. This will clear all license data and allow you to start fresh.', 'buddyboss' ); ?>
			</p>
			<form method="post" action="" name="<?php echo esc_attr( $pluginId ); ?>_reset_license_form" style="margin-top: 10px;">
				<table class="form-table">
					<tr>
						<td colspan="2">
							<?php wp_nonce_field( 'mothership_reset_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="reset">
							<input type="submit"
								value="<?php esc_attr_e( 'Reset License Settings', 'buddyboss' ); ?>"
								class="button button-secondary button-link-delete"
								onclick="return confirm('<?php echo esc_js( __( 'This will clear all license data including your license key. You will need to re-enter your license key to activate again. Continue?', 'buddyboss' ) ); ?>');" />
						</td>
					</tr>
				</table>
			</form>
		</div>
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
