<?php
/**
 * BuddyBoss TutorLMS Integration Class.
 *
 * @package BuddyBoss\TutorLMS
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BB TutorLMS class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_TutorLMS_Integration extends BP_Integration {

	/**
	 * BB_TutorLMS_Integration constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->start(
			'tutorlms',
			__( 'TutorLMS', 'buddyboss' ),
			'tutorlms',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();

		require_once dirname( __FILE__ ) . '/includes/class-bb-tutorlms-group.php';
//		new BB_TutorLMS_Group();
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * Register TutorLMS setting tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( buddypress()->integration_dir ) . $this->id . '/bb-tutorlms-admin-tab.php';

		new BB_TutorLMS_Admin_Integration_Tab(
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
