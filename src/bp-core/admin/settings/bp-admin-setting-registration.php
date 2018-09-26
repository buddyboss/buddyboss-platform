<?php

class BP_Admin_Setting_Registration extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name        = 'Registration';
		$this->tab_slug        = 'bp-registration';
		$this->tab_description = __( 'Associate WordPress Pages with the following BuddyPress Registration pages.', 'buddyboss' );
		$this->section_name    = 'bp_registration';
		$this->section_label   = __( 'Registration Settings', 'buddyboss' );
	}

	public function is_active() {
		return bp_get_signup_allowed();
	}

	public function register_fields() {
		$existing_pages = bp_core_get_directory_page_ids();
		$static_pages = bp_core_admin_get_static_pages();

		foreach ($static_pages as $name => $label) {
			$this->add_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', [], compact('existing_pages', 'name', 'label') );
		}
	}
}

return new BP_Admin_Setting_Registration;
