<?php
/**
 * BuddyBoss LearnDash Integration Class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp learndash class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Learndash_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'learndash',
			__( 'LearnDash', 'buddyboss' ),
			'learndash',
			array(
				'required_plugin' => 'sfwd-lms/sfwd_lms.php',
			)
		);
	}

	/**
	 * Register admin setting tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_admin_integration_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-learndash-tab.php';

		new BP_Learndash_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			array(
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			)
		);
	}

	/**
	 * Load function files
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function includes( $includes = array() ) {
		parent::includes(
			array(
				'functions',
				'filters',
				'groups-sync/loader.php',
				'core/Core.php',
			)
		);
	}
}
