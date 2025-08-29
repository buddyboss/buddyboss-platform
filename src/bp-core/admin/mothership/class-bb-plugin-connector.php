<?php
/**
 * BuddyBoss Plugin Connector
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

namespace BuddyBoss\Core\Admin\Mothership;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin Connector class for BuddyBoss Platform.
 */
class BB_Plugin_Connector {

	/**
	 * Instance of this class.
	 *
	 * @var BB_Plugin_Connector
	 */
	private static $instance = null;

	/**
	 * Plugin ID.
	 *
	 * @var string
	 */
	public $plugin_id = 'buddyboss-platform';

	/**
	 * Plugin prefix.
	 *
	 * @var string
	 */
	public $plugin_prefix = 'buddyboss';

	/**
	 * Get singleton instance.
	 *
	 * @return BB_Plugin_Connector
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// For local development, you can define the API URL.
		if ( defined( 'BUDDYBOSS_MOTHERSHIP_API_BASE_URL' ) ) {
			// API URL is already defined as a constant.
		}
	}

	/**
	 * Get license activation status.
	 *
	 * @return bool License activation status.
	 */
	public function get_license_activation_status() {
		return (bool) get_option( 'buddyboss_platform_license_activation_status', false );
	}

	/**
	 * Update license activation status.
	 *
	 * @param bool $status The status to update.
	 */
	public function update_license_activation_status( $status ) {
		update_option( 'buddyboss_platform_license_activation_status', $status );
	}

	/**
	 * Get license key.
	 *
	 * @return string License key.
	 */
	public function get_license_key() {
		return (string) get_option( 'buddyboss_platform_license_key', '' );
	}

	/**
	 * Update license key.
	 *
	 * @param string $license_key The license key to update.
	 */
	public function update_license_key( $license_key ) {
		update_option( 'buddyboss_platform_license_key', $license_key );
	}

	/**
	 * Get domain.
	 *
	 * @return string The domain.
	 */
	public function get_domain() {
		return wp_parse_url( get_home_url(), PHP_URL_HOST );
	}
}

/**
 * Credentials handler class.
 */
class BB_Credentials {

	/**
	 * Get license key from options or environment.
	 *
	 * @return string License key.
	 */
	public static function get_license_key() {
		// Check environment variable first.
		if ( defined( 'BUDDYBOSS_LICENSE_KEY' ) ) {
			return BUDDYBOSS_LICENSE_KEY;
		}

		$connector = BB_Plugin_Connector::get_instance();
		return $connector->get_license_key();
	}

	/**
	 * Store license key.
	 *
	 * @param string $license_key The license key to store.
	 * @throws \Exception If license key is set in environment.
	 */
	public static function store_license_key( $license_key ) {
		if ( defined( 'BUDDYBOSS_LICENSE_KEY' ) ) {
			throw new \Exception( esc_html__( 'License key is defined in environment and cannot be updated.', 'buddyboss' ) );
		}

		$connector = BB_Plugin_Connector::get_instance();
		$connector->update_license_key( $license_key );
	}

	/**
	 * Get activation domain.
	 *
	 * @return string Activation domain.
	 */
	public static function get_activation_domain() {
		if ( defined( 'BUDDYBOSS_ACTIVATION_DOMAIN' ) ) {
			return BUDDYBOSS_ACTIVATION_DOMAIN;
		}

		$connector = BB_Plugin_Connector::get_instance();
		return $connector->get_domain();
	}

	/**
	 * Get email for API authentication.
	 *
	 * @return string Email address.
	 */
	public static function get_email() {
		if ( defined( 'BUDDYBOSS_API_EMAIL' ) ) {
			return BUDDYBOSS_API_EMAIL;
		}
		return get_option( 'buddyboss_api_email', '' );
	}

	/**
	 * Get API token for authentication.
	 *
	 * @return string API token.
	 */
	public static function get_api_token() {
		if ( defined( 'BUDDYBOSS_API_TOKEN' ) ) {
			return BUDDYBOSS_API_TOKEN;
		}
		return get_option( 'buddyboss_api_token', '' );
	}

	/**
	 * Check if credential is set in environment or constants.
	 *
	 * @param string $credential The credential to check.
	 * @return bool True if set in environment, false otherwise.
	 */
	public static function is_credential_set_in_environment( $credential ) {
		switch ( $credential ) {
			case 'license_key':
				return defined( 'BUDDYBOSS_LICENSE_KEY' );
			case 'domain':
				return defined( 'BUDDYBOSS_ACTIVATION_DOMAIN' );
			case 'email':
				return defined( 'BUDDYBOSS_API_EMAIL' );
			case 'api_token':
				return defined( 'BUDDYBOSS_API_TOKEN' );
			default:
				return false;
		}
	}
}