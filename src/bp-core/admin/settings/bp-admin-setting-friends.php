<?php

class BP_Admin_Setting_Friends extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name      = 'Friends';
		$this->tab_slug      = 'bp-friends';
		$this->section_name  = 'bp_friends';
		$this->section_label = __( 'Connection Settings', 'buddyboss' );
	}

	protected function is_active() {
		return bp_is_active( 'friends' );
	}

	public function register_fields() {
		if ( bp_is_active( 'messages' ) ) {
			$this->add_field( 'bp-force-friendship-to-message', __( 'Messaging', 'buddyboss' ), 'bp_admin_setting_callback_force_friendship_to_message', 'bp_admin_sanitize_callback_force_friendship_to_message' );
		}
	}

	public function bp_admin_setting_callback_force_friendship_to_message() {
	?>
	    <input id="bp-force-friendship-to-message" name="bp-force-friendship-to-message" type="checkbox" value="1" <?php checked( bp_force_friendship_to_message( false ) ); ?> />
	    <label for="bp-force-friendship-to-message"><?php _e( 'Require users to be connected before they can message each other', 'buddyboss' ); ?></label>
	<?php
	}
}

return new BP_Admin_Setting_Friends;
