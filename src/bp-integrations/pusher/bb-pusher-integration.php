<?php
/**
 * BuddyBoss Pusher Integration Class.
 *
 * @package BuddyBoss\Pusher
 *
 * @since BuddyBoss 2.1.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BB Pusher class.
 *
 * @since BuddyBoss 2.1.4
 */
class BB_Pusher_Integration extends BP_Integration {

	/**
	 * BB_Pusher_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'pusher',
			__( 'Pusher', 'buddyboss' ),
			'pusher',
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
	 * @param array $includes Array of file paths to include.
	 *
	 * @since BuddyBoss 2.1.4
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( buddypress()->integration_dir ) . $this->id . '/';

		$includes = array(
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
	 * Register Pusher setting tab.
	 *
	 * @since BuddyBoss 2.1.4
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( buddypress()->integration_dir ) . $this->id . '/bb-pusher-admin-tab.php';

		new BB_Pusher_Admin_Integration_Tab(
			"bb-{$this->id}",
			$this->name,
			array(
				'root_path'       => trailingslashit( buddypress()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( buddypress()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
