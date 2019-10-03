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
			array(
				'required_plugin' => 'appboss/appboss.php',
			)
		);
	}

	/**
	 * Register admin setting tab, only if AppBoss plugin is disabled
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_admin_integration_tab() {

		if ( ! is_plugin_active( $this->required_plugin ) ) {

			require_once trailingslashit( $this->path ) . 'bp-admin-appboss-tab.php';

			new BP_Appboss_Admin_Integration_Tab(
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
}
