<?php
/**
 * BuddyBoss Compatibility Integration Class.
 *
 * @since BuddyBoss 1.1.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp compatibility class.
 *
 * @since BuddyBoss 1.1.6
 */
class BP_Compatibility_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'compatibility',
			__( 'Compatibility', 'buddyboss' ),
			'compatibility',
			[
			'required_plugin' => array(),
			]
		);
	}

	/**
	 * Register admin setting tab, only if AppBoss plugin is disabled
	 *
	 * @since BuddyBoss 1.1.6
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( $this->path ) . 'bp-admin-compatibility-tab.php';

		new BP_Compatibility_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			[
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			]
		);
	}
}
