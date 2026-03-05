<?php
/**
 * Add admin Connections settings page in Dashboard->BuddyBoss->Settings
 *
 * Legacy settings class — fields removed, now managed via Settings 2.0
 * (Members → Member Connection panel).
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Connection settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Friends extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Connections', 'buddyboss' );
		$this->tab_name  = 'bp-friends';
		$this->tab_order = 60;
	}

	public function is_active() {
		return bp_is_active( 'friends' );
	}

	public function register_fields() {

		/**
		 * Fires to register Friends tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Friends.
		 */
		do_action( 'bp_admin_setting_friends_register_fields', $this );
	}
}

return new BP_Admin_Setting_Friends();
