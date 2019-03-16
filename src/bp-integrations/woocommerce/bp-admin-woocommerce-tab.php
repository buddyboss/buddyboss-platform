<?php
/**
 * WooCommerce integration admin tab
 *
 * @package BuddyBoss\WooCommerce
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Setup WooCommerce integration admin tab class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Woocommerce_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_order = 30;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	public function settings_save() {
		$settings = $_REQUEST;
		error_log(print_r($settings, true));

		register_setting('bbms-settings', 'bbms-settings', BuddyBoss\Integrations\BbmsHelper::bbmsSettingsSanitize($settings));

		/**
		 * After Learndash-WooCommerce Integration settings are saved
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_learndash_woocommerce_fields_updated', $settings);

	}

	public function register_fields() {

		$this->add_section(
			'woocommerce-section', // Unique Identifier
			__('General Settings ', 'buddyboss') //Title
		);

		// If Enabled/Disabled
		$this->add_checkbox_field(
			'bp-learndash-woocommerce', // Unique Identifier
			__('Enable', 'buddyboss'), //Title
			['input_text' => __("Enroll user in Learndash course(s) after purchasing WooCommerce product.", 'bbms'), 'buddyboss']); //Callback

	}
}