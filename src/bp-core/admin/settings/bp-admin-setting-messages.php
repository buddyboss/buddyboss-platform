<?php

class BP_Admin_Setting_Messages extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Messages', 'buddyboss' );
		$this->tab_name  = 'bp-messages';
		$this->tab_order = 30;
	}

	public function is_active() {
		return bp_is_active( 'messages' );
	}

	public function register_fields() {
		$this->add_section( 'bp_messages', __( 'Messages Settings', 'buddyboss' ) );
	}
}

return new BP_Admin_Setting_Messages;
