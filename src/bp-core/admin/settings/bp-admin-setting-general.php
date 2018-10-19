<?php

class BP_Admin_Setting_General extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'General', 'buddyboss' );
		$this->tab_name  = 'buddypress';

		$this->register_fields();
	}

	public function register_fields() {
		$this->add_section( 'bp_main', __( 'Main Settings', 'buddyboss' ) );
		$this->add_field( 'hide-loggedout-adminbar', __( 'Toolbar', 'buddyboss' ), 'bp_admin_setting_callback_admin_bar', 'intval' );
		$this->add_field( 'bp-disable-account-deletion', __( 'Account Deletion', 'buddyboss' ), 'bp_admin_setting_callback_account_deletion', 'intval' );
	}
}

return new BP_Admin_Setting_General;
