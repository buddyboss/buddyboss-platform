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
		$this->tab_order = 20;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp-memberpress-section';
	}

	public function settings_save() {

		$settings = $_REQUEST;

		//@See : bp-core-options.php->bp_update_option()
		bp_update_option('bp-memberpess_enabled', $settings['bp-memberpess_enabled']);

		$isEnabled = bp_get_option('bp-memberpess_enabled');
		error_log($isEnabled);

		/**
		 * After Memberpress Integration settings are saved
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_integrations_memberpess_fields_updated', $settings);

	}

	public function register_fields() {

		$this->add_section(
			$this->current_section, // Unique Identifier
			__('General Settings ', 'buddyboss') //Title
		);

		// If Enabled/Disabled
		$this->add_checkbox_field(
			'bp-memberpess_enabled', // Unique Identifier
			__('Enable', 'buddyboss'), //Title
			['input_text' => __("Learndash-MemberPress Integration", 'buddyboss'),
				'input_description' => __("Enroll user in Learndash course(s) after purchasing MemberPress membership.")]); //Callback

		// $this->render_input_description("View Logs");

	}
}
