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
		$this->tab_order = 40;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp-woocommerce-section';
	}

	public function settings_save() {
		$settings = $_REQUEST;

		//@See : bp-core-options.php
		bp_update_option('bp-woocommerce_enabled', $settings['bp-woocommerce_enabled']);
	}

	public function register_fields() {

		$this->add_section(
			$this->current_section, // Unique Identifier
			__('WooCommerce Settings ', 'buddyboss') //Title,

		);
		if (defined('LEARNDASH_VERSION')) {

			// If Enabled/Disabled
			$this->add_checkbox_field(
				'bp-woocommerce_enabled', // Unique Identifier
				__('LearnDash Enrollment', 'buddyboss'), //Title
				['input_text' => __("Enroll members into LearnDash course(s) after purchasing WooCommerce products", 'buddyboss'),
					'input_description' => __("Configure the course enrollment when editing the product", 'buddyboss')]); //Callback

			$this->add_field(
				'bp-woocommerce_link', // Unique Identifier
				'', //Label
				'wcRenderAnchor', //Callback
				'', //Fields arguments
				WC_PRODUCT_SLUG//Callback arguments
			);

		} else {

			$this->add_field(
				'bp-woocommerce_ld_text', // Unique Identifier
				'', //Label
				'wcNoLearnDashText', //Callback
				'', //Fields arguments
				'WooCommerce' //Callback arguments
			);

		}
	}

	/**
	 * Case-1: WooCommerce is inActive but form_html would still be executed
	 * Case-2: WooCommerce is Active but LD is InActive
	 * Case-3: LearnDash and WooCommerce both are Active
	 */
	public function form_html() {

		if (!is_plugin_active($this->required_plugin)) {

			$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
			if (is_file($this->intro_template)) {
				require $this->intro_template;
			}

		} else if (!defined('LEARNDASH_VERSION')) {
			//NOTE : LearnDash is InActive

			settings_fields($this->tab_name);
			$this->bp_custom_do_settings_sections($this->tab_name);

		} else {
			//NOTE : LearnDash and WooCommerce both are Active, so display form
			parent::form_html();
		}

	}
}