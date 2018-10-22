<?php

class BP_Admin_Setting_Registration extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Registration', 'buddyboss' );
		$this->tab_name  = 'bp-registration';
		$this->tab_order = 35;
	}

	public function register_fields() {
		$this->add_section( 'bp_registration', __( 'Registration Settings', 'buddyboss' ) );
	}
}

return new BP_Admin_Setting_Registration;
