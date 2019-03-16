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
		error_log("BP_Memberpress_Admin_Integration_Tab->initialize()");
		$this->tab_order = 40;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->template = $this->root_path . '/templates/';
		$this->plugin_is_active = false;
		if (defined('MEPR_PLUGIN_SLUG')) {
			$this->plugin_is_active = true;
		}

	}

	public function settings_save() {
		error_log("BP_Memberpress_Admin_Integration_Tab->settings_save()");

		$settings = $_REQUEST;
		error_log(print_r($settings, true));

		register_setting('_bp-learndash-memberpess', '_bp-learndash-memberpess');

		/**
		 * After Learndash-Memberpress Integration settings are saved
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_integrations_memberpess_fields_updated', $settings);

	}

	public function register_fields() {

		$this->add_section(
			'memberpress-section', // Unique Identifier
			__('General Settings ', 'buddyboss') //Title
		);

		// If Enabled/Disabled
		$this->add_checkbox_field(
			'_bp-learndash-memberpess', // Unique Identifier
			__('Enable', 'buddyboss'), //Title
			['input_text' => __("Enroll user in Learndash course(s) after purchasing MemberPress membership.", 'bbms'), 'buddyboss']); //Callback

	}
}
