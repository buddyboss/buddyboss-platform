<?php
/**
 * AppBoss integration admin tab
 *
 * @package BuddyBoss\AppBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup AppBoss integration admin tab class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Appboss_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	public function initialize() {
		$this->tab_order = 40;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp-appboss-section';
	}

	public function settings_save() {
		$settings = $_REQUEST;

		//@See : bp-core-options.php->bp_update_option()
		bp_update_option('bp-appboss_enabled', $settings['bp-appboss_enabled']);

		$isEnabled = bp_get_option('bp-appboss_enabled');
		error_log($isEnabled);

		/**
		 * After Appboss Integration settings are saved
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_integrations_appboss_fields_updated', $settings);
	}

	public function register_fields() {
		$this->add_section(
			$this->current_section,
			__('General Settings', 'buddyboss')
		);

		if (defined('LEARNDASH_VERSION')) {
			// If Enabled/Disabled
			$this->add_checkbox_field(
				'bp-appboss_enabled', // Unique Identifier
				__('Enable', 'buddyboss'), //Title
				['input_text' => __("Learndash-AppBoss Integration", 'buddyboss'),
					'input_description' => __("Enroll user in Learndash course(s) after purchasing In-App purchases.", 'buddyboss')]); //Callback

		}
	}
}
