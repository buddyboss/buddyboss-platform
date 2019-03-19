<?php
/**
 * BuddyBoss Membership Integration Class.
 *
 * @package BuddyBoss\Membership
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup the BuddyBoss Platform Memberships class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Memberships_Integration extends BP_Integration {

	public function __construct() {

		// Calling parent. Locate BP_Integration->start()
		$this->start(
			'memberships', // Internal identifier of integration.
			__('Memberships', 'buddyboss'), // Internal integration name.
			'memberships', //Path for includes.
			[
				'required_plugin' => 'sfwd-lms/sfwd_lms.php', //Params
			]
		);
	}

	/**
	 * Memberships Integration Tab
	 * @return {HTML} - renders html in bp-admin-memberships-tab.php
	 */
	public function setup_admin_integartion_tab() {
		require_once trailingslashit($this->path) . 'bp-admin-memberships-tab.php';

		new BP_Memberships_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			[
				'root_path' => $this->path,
				'root_url' => $this->url,
				'required_plugin' => $this->required_plugin,
			]
		);
	}

	/**
	 * Memberships includes additional files such as any library or functions or dependencies
	 * @return {file(s)} - execute php from included files
	 */
	public function includes($includes = array()) {
		// Calling Parent
		parent::includes([
			'vendor/autoload.php',
		]);

		$bbMemberships = BuddyBoss\Memberships\Classes\BpMemberships::getInstance();
	}

}