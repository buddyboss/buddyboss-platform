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
			__( 'reCAPTCHA', 'buddyboss' ),
			'recaptcha',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();
	}

	/**
	 * Includes.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $includes Array of file paths to include.
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( buddypress()->integration_dir ) . $this->id . '/';

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
	 * @since BuddyBoss 2.5.60
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( buddypress()->integration_dir ) . $this->id . '/classes/class-bb-recaptcha-admin-tab.php';

		new BB_Recaptcha_Admin_Tab(
			"bb-$this->id",
			$this->name,
			array(
				'root_path'       => trailingslashit( buddypress()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( buddypress()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
