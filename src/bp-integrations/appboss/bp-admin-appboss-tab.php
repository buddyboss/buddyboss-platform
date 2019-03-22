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
				['input_text' => __("LearnDash-AppBoss Integration", 'buddyboss'),
					'input_description' => __("Enroll user in LearnDash course(s) after purchasing In-App purchases.", 'buddyboss')]); //Callback

			$this->add_field(
				'bp-appboss_link', // Unique Identifier
				'', //Label
				'appBossRenderAnchor', //Callback
				'', //Fields arguments
				APPBOSS_PROD_TYPE//Callback arguments
			);

		} else {

			$this->add_field(
				'bp-appboss_ld_text', // Unique Identifier
				'', //Label
				'appBossNoLearnDashText', //Callback,
				'', //Fields arguments
				'AppBoss' //Callback arguments
			);

		}

	}

	/**
	 * @todo : Test more
	 * Case-1: AppBoss is inActive but form_html would still be executed
	 * Case-2: AppBoss is Active but LD is InActive
	 * Case-3: LearnDash and AppBoss both are Active
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
			//NOTE : LearnDash and AppBoss both are Active, so display form
			parent::form_html();
		}

	}
}
