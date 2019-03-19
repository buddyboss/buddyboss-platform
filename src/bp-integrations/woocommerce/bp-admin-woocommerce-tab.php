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
	protected $current_section;

	public function initialize() {
		$this->tab_order = 50;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp-woocommerce-section';
	}

	public function settings_save() {
		$settings = $_REQUEST;

		//@See : bp-core-options.php
		bp_update_option('bp-woocommerce_enabled', $settings['bp-woocommerce_enabled']);

		$isEnabled = bp_get_option('bp-woocommerce_enabled');

		/**
		 * After WooCommerce Integration settings are saved
		 * @since BuddyBoss 1.0.0
		 */
		do_action('bp_integrations_woocommerce_fields_updated', $settings);

	}

	public function register_fields() {

		$this->add_section(
			$this->current_section, // Unique Identifier
			__('General Settings ', 'buddyboss') //Title
		);

		// If Enabled/Disabled
		$this->add_checkbox_field(
			'bp-woocommerce_enabled', // Unique Identifier
			__('Enable', 'buddyboss'), //Title
			['input_text' => __("Enroll user in Learndash course(s) after purchasing WooCommerce product.", 'bbms'), 'buddyboss']); //Callback

	}
}