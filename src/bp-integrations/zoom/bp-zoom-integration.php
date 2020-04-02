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
		add_action( 'bp_core_install', array( $this, 'db_install_zoom_meetings' ) );
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
				'template',
				'functions',
				'includes/class-bp-group-zoom-meeting-template.php',
				'includes/class-bp-group-zoom-meeting.php',
				'includes/class-bp-group-zoom-extension.php',
				'api/class-bp-zoom-api.php',
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

	/**
	 * Install database tables for the Groups zoom meetings.
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function db_install_zoom_meetings() {
		$sql             = array();
		$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
		$bp_prefix       = bp_core_get_table_prefix();

		$sql[] = "CREATE TABLE {$bp_prefix}bp_groups_zoom_meetings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				title varchar(300) NOT NULL,
				user_id varchar(150) NOT NULL,
				start_date datetime NOT NULL,
				timezone varchar(150) NOT NULL,
				duration int(11) NOT NULL,
				join_before_host bool default 0,
				host_video bool default 0,
				participants_video bool default 0,
				mute_participants bool default 0,
				auto_recording varchar(75) default 'none',
				alternative_host_ids text NULL,
				zoom_details text NOT NULL,
				zoom_start_url text NOT NULL,
				zoom_join_url text NOT NULL,
				zoom_meeting_id text NOT NULL,
				KEY group_id (group_id)
			) {$charset_collate};";

		dbDelta( $sql );
	}
}
