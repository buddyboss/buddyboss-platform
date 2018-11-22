<?php

/**
 * Class LearnDash_Settings_Page_BuddyPress_Groups_Report
 */
class LearnDash_Settings_Page_BuddyPress_Groups_Report extends LearnDash_Settings_Page {

	public function __construct() {
		$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
		$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
		$this->settings_page_id      = 'learndash_lms_settings_buddypress_groups_reports';
		$this->settings_page_title   = esc_html_x( 'Group Reports for LearnDash', 'Settings Tab Label', 'ld_bp_groups_reports' );
		$this->settings_tab_title    = esc_html_x( 'BuddyPress Groups Reports', 'Settings Page Title', 'ld_bp_groups_reports' );
		$this->settings_tab_priority = 35;

		parent::__construct();
	}
}