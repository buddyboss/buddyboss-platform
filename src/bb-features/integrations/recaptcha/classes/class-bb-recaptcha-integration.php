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
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $includes Array of file paths to include.
	 */
	public function includes( $includes = array() ) {
		$slashed_path = $this->get_integration_dir();

		$includes = array(
			'actions',
			'filters',
			'functions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			$paths = array(

				// Passed with no extension.
				'bb-' . $this->id . '/bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bb-' . $this->id . '-' . $file,
				'bb-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					require $slashed_path . $path;
					break;
				}
			}
		}
	}

	/**
	 * Register Recaptcha setting tab.
	 *
	 * Legacy admin tab removed — reCAPTCHA settings are managed
	 * via Settings 2.0 at bb-settings&tab=recaptcha.
	 *
	 * @since BuddyBoss 2.5.60
	 * @since BuddyBoss [BBVERSION] Removed legacy integration tab.
	 */
	public function setup_admin_integration_tab() {
		// No-op: Settings managed via Settings 2.0.
	}
}
