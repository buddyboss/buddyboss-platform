<?php

class BP_Admin_Setting_Pages extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name        = 'Pages';
		$this->tab_slug        = 'bp-pages';
		$this->tab_description = __( 'Associate a WordPress Page with each BuddyPress component directory.', 'buddyboss' );
		$this->section_name    = 'bp_pages';
		$this->section_label   = __( 'Pages Settings', 'buddyboss' );
	}

	public function register_fields() {
		$existing_pages = bp_core_get_directory_page_ids();
		$directory_pages = bp_core_admin_get_directory_pages();

		foreach ($directory_pages as $name => $label) {
			$this->add_field( $name, $label, 'bp_admin_setting_callback_page_directory_dropdown', [], compact('existing_pages', 'name', 'label') );
		}
	}
}

return new BP_Admin_Setting_Pages;
