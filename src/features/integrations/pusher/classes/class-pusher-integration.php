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
		// Load includes from new structure.
		if ( file_exists( $this->path . 'includes/functions.php' ) ) {
			require_once $this->path . 'includes/functions.php';
		}
	}

	/**
	 * Register Pusher setting tab.
	 *
	 * @since BuddyBoss 2.1.4
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( $this->path ) . 'admin/settings.php';

		new BB_Pusher_Admin_Integration_Tab(
			"bb-{$this->id}",
			$this->name,
			array(
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
