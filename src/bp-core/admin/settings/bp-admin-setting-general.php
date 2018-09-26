<?php

class BP_Admin_Setting_General extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name      = 'General';
		$this->tab_slug      = 'bp-general';
		$this->section_name  = 'bp_main';
		$this->section_label = __( 'Main Settings', 'buddyboss' );
	}

	public function register_fields() {
		$this->add_field( 'hide-loggedout-adminbar', __( 'Toolbar', 'buddyboss' ), 'bp_admin_setting_callback_admin_bar', 'intval' );
		$this->add_field( 'bp-disable-account-deletion', __( 'Account Deletion', 'buddyboss' ), 'bp_admin_setting_callback_account_deletion', 'intval' );
	}

	public function bp_admin_setting_callback_admin_bar() {
		$this->checkbox('hide-loggedout-adminbar', __( 'Show the Toolbar for logged out users', 'buddyboss' ), 'bp_hide_loggedout_adminbar');
	}

	public function bp_admin_setting_callback_account_deletion() {
		$this->checkbox('bp-disable-account-deletion', __( 'Allow registered members to delete their own accounts', 'buddyboss' ), 'bp_disable_account_deletion');
	}
}

return new BP_Admin_Setting_General;
