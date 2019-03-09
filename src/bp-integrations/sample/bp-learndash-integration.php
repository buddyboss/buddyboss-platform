<?php
/**
 * Sample integration main class
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup sample integration class
 * 
 * @since BuddyBoss 1.0.0
 */
class BP_Learndash_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'sample',
			__( 'Sample', 'buddyboss' ),
			'sample',
			[
				// 'required_plugin' => 'sfwd-lms/sfwd_lms.php'
			]
		);
	}

	public function setup_admin_integartion_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-sample-tab.php';

		new BP_Sample_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			[
				'root_path' => $this->path,
				'root_url'  => $this->url,
				'required_plugin' => $this->required_plugin,
			]
		);
	}

	public function includes( $includes = array() ) {
		parent::includes([
			// 'functions',
		]);
	}
}
