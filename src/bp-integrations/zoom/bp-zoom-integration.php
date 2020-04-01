<?php
/**
 * BuddyBoss Zoom Integration Class.
 *
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp zoom class.
 *
 * @since BuddyBoss 1.2.10
 */
class BP_Zoom_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'zoom',
			__( 'Zoom Conference', 'buddyboss' ),
			'zoom',
			array(
				'required_plugin' => array(),
			)
		);
	}

	public function setup_actions() {
		add_action( 'bp_include', array( $this, 'includes' ), 8 );
		parent::setup_actions();
	}

	/**
	 * Load function files
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function includes( $includes = array() ) {
		parent::includes(
			array(
				'api/class-bp-zoom-api.php',
				'functions',
			)
		);
	}

	/**
	 * Register zoom setting tab
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( $this->path ) . 'bp-zoom-admin-tab.php';

		new BP_Zoom_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			array(
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
