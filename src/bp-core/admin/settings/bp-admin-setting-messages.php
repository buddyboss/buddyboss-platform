<?php
/**
 * Add admin Credit settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Messages extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Messages', 'buddyboss' );
		$this->tab_name  = 'bp-messages';
		$this->tab_order = 30;
	}

	//Check if messages are enabled
	public function is_active() {
		return bp_is_active( 'messages' );
	}

	//Register setting fields
	public function register_fields() {
		$this->add_section( 'bp_messages', __( 'Messages Settings', 'buddyboss' ) );
	}
}

return new BP_Admin_Setting_Messages;
