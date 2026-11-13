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
class BBoss_Pusher_Integration extends BBoss_Integration {

	/**
	 * BBoss_Pusher_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'pusher',
			'Pusher',
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
		$slashed_path = trailingslashit( bboss()->integration_dir ) . $this->id . '/';

		$includes = array(
			'functions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			$paths = array();

			// Files keep the original `bb-` name on release; a generated edition renames them `bboss-`.
			foreach ( array( 'bboss-', 'bb-' ) as $prefix ) {
				$paths = array_merge(
					$paths,
					array(
						// Passed with no extension.
						$prefix . $this->id . '/' . $prefix . $this->id . '-' . $file . '.php',
						$prefix . $this->id . '-' . $file . '.php',
						$prefix . $this->id . '/' . $file . '.php',
					)
				);
			}

			// Passed with extension.
			$paths[] = $file;
			foreach ( array( 'bboss-', 'bb-' ) as $prefix ) {
				$paths[] = $prefix . $this->id . '-' . $file;
				$paths[] = $prefix . $this->id . '/' . $file;
			}

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

		require_once trailingslashit( bboss()->integration_dir ) . $this->id . '/bboss-pusher-admin-tab.php';

		new BBoss_Pusher_Admin_Integration_Tab(
			"bb-{$this->id}",
			$this->name,
			array(
				'root_path'       => trailingslashit( bboss()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( bboss()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
