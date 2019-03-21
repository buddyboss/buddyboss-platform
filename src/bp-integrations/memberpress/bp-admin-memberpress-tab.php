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
		bp_update_option('bp-memberpress_enabled', $settings['bp-memberpress_enabled']);
	}

	public function register_fields() {

		$this->add_section(
			$this->current_section, // Unique Identifier
			__('General Settings ', 'buddyboss') //Title
		);

		if (defined('LEARNDASH_VERSION')) {

			// If Enabled/Disabled
			$this->add_checkbox_field(
				'bp-memberpress_enabled', // Unique Identifier
				__('Enable', 'buddyboss'), //Title
				['input_text' => __("LearnDash-MemberPress Integration", 'buddyboss'),
					'input_description' => __("Enroll user in LearnDash course(s) after purchasing MemberPress membership.", 'buddyboss')]); //Callback

			$this->add_field(
				'bp-memberpress_link', // Unique Identifier
				'', //Label
				'mpRenderAnchor', //Callback
				'', //Fields arguments
				'memberpress' //Callback arguments
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

	public function form_html() {
		if (defined('LEARNDASH_VERSION')) {
			settings_fields($this->tab_name);
			$this->bp_custom_do_settings_sections($this->tab_name);

			printf(
				'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
				esc_attr__('Save Settings', 'buddyboss')
			);
		}
	}

}
