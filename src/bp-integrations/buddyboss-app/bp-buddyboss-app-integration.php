<?php
/**
 * BuddyBoss App Integration Class.
 *
 * @package BuddyBoss\App
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp app class.
 *
 * @since BuddyBoss 1.0.0
 */
class BBoss_App_Integration extends BBoss_Integration {

	public function __construct() {
		$this->start(
			'buddyboss-app',
			'BuddyBoss App',
			'buddyboss-app',
			array(
				'required_plugin' => 'buddyboss-app/buddyboss-app.php',
			)
		);
	}

	/**
	 * Register admin setting tab, only if BuddyBoss App plugin is disabled
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_admin_integration_tab() {

		if ( ! is_plugin_active( $this->required_plugin ) ) {

			require_once trailingslashit( $this->path ) . 'bp-admin-buddyboss-app-tab.php';

			new BBoss_App_Admin_Integration_Tab(
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
