<?php
/**
 * MemberPress integration admin tab
 *
 * @package BuddyBoss\MemberPress
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup memberpress integration admin tab class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Memberpress_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	public function initialize() {
		$this->tab_order = 40;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	public function settings_save() {

		$settings = $_REQUEST;
		// error_log(print_r($settings, true));

		// register_setting('bbms-settings', 'bbms-settings', BuddyBoss\Integrations\BpMemberships::bbmsSettingsSanitize($settings));

		/**
		 * After Learndash-Memberpress Integration settings are saved
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_learndash_memberpess_fields_updated', $settings);

	}

	public function register_fields() {

		$this->add_section(
			'memberpress-section', // Unique Identifier
			__('General Settings ', 'buddyboss') //Title
		);

		// If Enabled/Disabled
		$this->add_checkbox_field(
			'bp-learndash-memberpess', // Unique Identifier
			__('Enable', 'buddyboss'), //Title
			['input_text' => __("Enroll user in Learndash course(s) after purchasing MemberPress membership.", 'bbms'), 'buddyboss']); //Callback

	}
}
