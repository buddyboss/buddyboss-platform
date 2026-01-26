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
	 * Includes.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $includes Array of file paths to include.
	 */
	public function includes( $includes = array() ) {
		// Load includes from new structure.
		$files = array( 'actions', 'filters', 'functions' );

		foreach ( $files as $file ) {
			$file_path = $this->path . 'includes/' . $file . '.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Register Recaptcha setting tab.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( $this->path ) . 'admin/settings.php';

		new BB_Recaptcha_Admin_Tab(
			"bb-$this->id",
			$this->name,
			array(
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
