<?php

class BP_Admin_Setting_Registration extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name        = 'Registration';
		$this->tab_slug        = 'bp-registration';
		$this->section_name    = 'bp_registration';
		$this->section_label   = __( 'Registration Settings', 'buddyboss' );
	}

	public function register_fields() {

	}
}

return new BP_Admin_Setting_Registration;
