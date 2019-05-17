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
		$this->tab_order = 30;
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp-memberpress-integration';
	}

	public function settings_save() {

		$settings = $_REQUEST;

		//@See : bp-core-options.php->bp_update_option()
		bp_update_option('bp-memberpress_enabled', $settings['bp-memberpress_enabled']);
	}

	public function register_fields() {

		$this->add_section(
			$this->current_section, // Unique Identifier
			__('MemberPress Settings ', 'buddyboss') //Title
		);

		if (defined('LEARNDASH_VERSION')) {

			// If Enabled/Disabled
			$this->add_checkbox_field(
				'bp-memberpress_enabled', // Unique Identifier
				__('LearnDash Enrollment', 'buddyboss'), //Title
				['input_text' => __("Enroll members in LearnDash course(s) after purchasing MemberPress memberships.", 'buddyboss'),
					'input_description' => __("Configure the course enrollment when editing the membership.", 'buddyboss')]); //Callback

			$this->add_field(
				'bp-memberpress_link', // Unique Identifier
				'', //Label
				'mpRenderAnchor', //Callback
				'', //Fields arguments
				MP_PRODUCT_SLUG//Callback arguments
			);
		} else {

			$this->add_field(
				'bp-memberpress_ld_text', // Unique Identifier
				'', //Label
				'mpNoLearnDashText', //Callback,
				'', //Fields arguments
				'MemberPress' //Callback arguments
			);

		}

	}

	/**
	 * Case-1: Memberpress is inActive(but form_html would still be executed)
	 * Case-2: Memberpress is Active but LD is InActive
	 * Case-3: LearnDash and Memberpress both are Active
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
			//NOTE : LearnDash and Memberpress both are Active, so display form
			parent::form_html();
		}

	}

}
