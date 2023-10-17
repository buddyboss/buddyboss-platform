<?php
/**
 * BuddyBoss TutorLMS Integration Class.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp tutorlms class.
 *
 * @since BuddyBoss 1.0.0
 */
class BB_TutorLMS_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'tutorlms',
			__( 'TutorLMS', 'buddyboss' ),
			'tutorlms',
			array(
				'required_plugin' => 'tutor/tutor.php',
			)
		);
	}

	/**
	 * Register admin setting tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_admin_integration_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-tutorlms-tab.php';

		new BB_TutorLMS_Admin_Integration_Tab(
			"bb-{$this->id}",
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
				'core/Core.php',
			)
		);
	}

}
