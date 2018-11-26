<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LearnDash_BuddyPress_Groups_Reports_LearnDash
 */
class LearnDash_BuddyPress_Groups_Reports_LearnDash {
	public function __construct() {

		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'learndash_settings_pages_init', array( $this, 'register_custom_settings_page' ) );
		add_action( 'learndash_settings_sections_init', [ $this, 'register_custom_submit_section' ] );
	}

	public function register_custom_settings_page() {

		include_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/settings/class-learndash-settings-page.php';

		LearnDash_Settings_Page_BuddyPress_Groups_Report::add_page_instance();
	}


	public function register_custom_submit_section() {

		include_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/settings/class-learndash-section-settings.php';
		include_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/settings/class-learndash-section-submit.php';
		include_once LEARNDASH_BUDDYPRESS_GROUP_REPORTS_PLUGIN_DIR . '/includes/settings/class-learndash-section-help.php';

		LearnDash_Settings_Section_BuddyPress_Groups_Reports::add_section_instance();
		LearnDash_Settings_Section_BuddyPress_Groups_Reports_Submit::add_section_instance();
		LearnDash_Settings_Section_BuddyPress_Groups_Reports_Help::add_section_instance();

	}
}

return new LearnDash_BuddyPress_Groups_Reports_LearnDash();
