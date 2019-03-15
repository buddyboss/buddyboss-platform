<?php
/**
 * BuddyBoss AppBoss Integration Class.
 *
 * @package BuddyBoss\AppBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp appboss class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Appboss_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'appboss',
			__( 'AppBoss', 'buddyboss' ),
			'appboss',
			[
				'required_plugin' => 'appboss/appboss.php'
			]
		);
	}

	public function setup_admin_integartion_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-appboss-tab.php';

		new BP_Appboss_Admin_Integration_Tab(
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
