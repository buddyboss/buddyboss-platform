<?php
/**
 * BuddyBoss Recaptcha Integration Class.
 *
 * @package BuddyBoss\Recaptcha
 *
 * @since BuddyBoss 2.5.60
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB Recaptcha class.
 *
 * @since BuddyBoss 2.5.60
 */
class BB_Recaptcha_Integration extends BP_Integration {

	/**
	 * BB_Recaptcha_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'recaptcha',
			'reCAPTCHA',
			'recaptcha',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();
	}

	/**
	 * Check if the reCAPTCHA feature is enabled in New Settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if feature is enabled.
	 */
	private function is_feature_enabled() {
		$active_features = bp_get_option( 'bb-active-features', array() );

		// If the feature key doesn't exist yet (pre-migration), treat as enabled
		// for backward compatibility.
		if ( ! array_key_exists( 'recaptcha', $active_features ) ) {
			return true;
		}

		return ! empty( $active_features['recaptcha'] );
	}

	/**
	 * Get the base directory for this integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string Integration base directory path.
	 */
	private function get_integration_dir() {
		return trailingslashit( buddypress()->plugin_dir ) . 'bb-features/integrations/' . $this->id . '/';
	}

	/**
	 * Get the base URL for this integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string Integration base URL.
	 */
	private function get_integration_url() {
		return trailingslashit( buddypress()->plugin_url ) . 'bb-features/integrations/' . $this->id . '/';
	}

	/**
	 * Includes.
	 *
	 * Loads reCAPTCHA integration files. Functions always load (public API
	 * used by New Settings). Actions and filters only load when the feature
	 * is enabled — they register the frontend hooks (login form, registration, etc.).
	 *
	 * @since BuddyBoss 2.5.60
	 * @since BuddyBoss [BBVERSION] Simplified path resolution, added feature gate.
	 *
	 * @param array $includes Array of file paths to include.
	 */
	public function includes( $includes = array() ) {
		$dir   = $this->get_integration_dir();
		$files = array( 'actions', 'filters', 'functions' );

		foreach ( $files as $file ) {
			// Skip frontend hooks (actions/filters) when the feature is disabled.
			// Functions file always loads — it's the public API used by Settings 2.0.
			if ( ! $this->is_feature_enabled() && 'functions' !== $file ) {
				continue;
			}

			$file_path = $dir . 'bb-recaptcha-' . $file . '.php';

			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Register Recaptcha setting tab.
	 *
	 * Legacy admin tab removed — reCAPTCHA settings are managed
	 * via New Settings at bb-settings&tab=recaptcha.
	 *
	 * @since BuddyBoss 2.5.60
	 * @since BuddyBoss [BBVERSION] Removed legacy integration tab.
	 */
	public function setup_admin_integration_tab() {
		// No-op: Settings managed via Settings 2.0.
	}
}
