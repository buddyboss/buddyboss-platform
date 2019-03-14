<?php
/**
 * BuddyBoss MemberPress Integration Class.
 *
 * @package BuddyBoss\MemberPress
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp MemberPress class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Memberpress_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'memberpress',
			__( 'MemberPress', 'buddyboss' ),
			'memberpress',
			[
				'required_plugin' => ' '
			]
		);
	}

	public function setup_admin_integartion_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-memberpress-tab.php';

		new BP_Memberpress_Admin_Integration_Tab(
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
			'functions',
			'core/Core.php',
		]);
	}
}
