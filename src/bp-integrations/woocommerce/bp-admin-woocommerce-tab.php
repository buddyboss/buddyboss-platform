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
		$this->tab_order = 30;
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
			__('General Settings ', 'buddyboss') //Title,

		);

		if (defined('LEARNDASH_VERSION')) {

			// If Enabled/Disabled
			$this->add_checkbox_field(
				'bp-woocommerce_enabled', // Unique Identifier
				__('Enable', 'buddyboss'), //Title
				['input_text' => __("LearnDash-WooCommerce Integration", 'buddyboss'),
					'input_description' => __("Enroll user in LearnDash course(s) after purchasing WooCommerce product.", 'buddyboss')]); //Callback

			$this->add_field(
				'bp-woocommerce_link', // Unique Identifier
				'', //Label
				'wcRenderAnchor', //Callback
				'', //Fields arguments
				WC_POST_TYPE//Callback arguments
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
	 * @todo : Need to find a way to avoid using form_html here since integration-tab-intro(templates/admin/integration-tab-intro.php) is NOT displayed when WooCommerce is inactive.
	 * Explore-1: Load below function in particular file and includes if Plugin is active
	 * Explore-2: Try to hide 'Save Settings' button via some javascript if possible?
	 */
	public function form_html() {

		settings_fields($this->tab_name);
		$this->bp_custom_do_settings_sections($this->tab_name);

		if (defined('LEARNDASH_VERSION')) {

			printf(
				'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
				esc_attr__('Save Settings', 'buddyboss')
			);
		} else {

		}
	}
}